<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Services\LrReceiptCalculationService;

class BackfillLrReceiptFinancials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lr-receipt:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill consignment_number, lorry_receipt_id, and financial columns for LR Receipt Records';

    /**
     * Execute the console command.
     */
    public function handle(LrReceiptCalculationService $calculationService): int
    {
        $this->info('Starting consignment_number backfill on invoice_items...');
        
        // 1. Copy Consignment Number custom field value to invoice_items.consignment_number
        Invoice::where('template_name', 'office_invoice')
            ->with('items.fields.customField')
            ->chunk(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    foreach ($invoice->items as $item) {
                        $consignmentField = $item->fields->first(function ($field) {
                            return $field->customField &&
                                ($field->customField->name === 'Consignment Number' ||
                                 $field->customField->label === 'Consignment Number');
                        });
                        if ($consignmentField) {
                            $item->update(['consignment_number' => trim((string) $consignmentField->string_answer)]);
                        }
                    }
                }
            });

        $this->info('Consignment numbers backfilled successfully.');

        $this->info('Starting lorry_receipt_id backfill on LR Receipt Records...');
        
        // 2. Link LR Receipt Record to its Lorry Receipt
        Invoice::where('template_name', 'lr_receipt')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $docketNumber = trim($record->invoice_number);
                    $lorryReceipt = LorryReceipt::where('company_id', $record->company_id)
                        ->where(function ($q) use ($docketNumber) {
                            $q->where('received_no_bilties', 'like', $docketNumber)
                              ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                              ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                              ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
                        })
                        ->first();
                    if ($lorryReceipt) {
                        $record->update(['lorry_receipt_id' => $lorryReceipt->id]);
                    }
                }
            });

        $this->info('Lorry receipt IDs backfilled successfully.');

        $this->info('Starting financial columns recalculation...');
        
        // 3. Recalculate financial columns for all existing LR Receipt Records
        Invoice::where('template_name', 'lr_receipt')
            ->chunk(100, function ($records) use ($calculationService) {
                foreach ($records as $record) {
                    $calculationService->recalculate($record);
                }
            });

        $this->info('Financial columns recalculation completed.');

        return Command::SUCCESS;
    }
}
