<?php

namespace App\Listeners;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Services\LrReceiptCalculationService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateOnOfficeInvoiceSaved implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create the event listener.
     */
    public function __construct(protected LrReceiptCalculationService $calculationService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(Invoice $invoice): void
    {
        if ($invoice->template_name !== Invoice::TEMPLATE_OFFICE_INVOICE) {
            return;
        }

        // 1. Copy Consignment Number custom field value -> invoice_items.consignment_number
        $invoice->loadMissing('items.fields.customField');

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

        // 2. Get all consignment numbers from this invoice's items
        $consignmentNumbers = $invoice->items()->pluck('consignment_number')->filter()->map(fn($v) => trim($v))->unique()->toArray();

        if (empty($consignmentNumbers)) {
            return;
        }

        // 3. Find all Lorry Receipts where received_no_bilties contains any of these consignment numbers using database LIKE queries
        $lorryReceipts = LorryReceipt::query()
            ->where('company_id', $invoice->company_id)
            ->where(function ($q) use ($consignmentNumbers) {
                foreach ($consignmentNumbers as $num) {
                    $q->orWhere('received_no_bilties', 'like', $num)
                      ->orWhere('received_no_bilties', 'like', $num . ',%')
                      ->orWhere('received_no_bilties', 'like', '%,' . $num)
                      ->orWhere('received_no_bilties', 'like', '%,' . $num . ',%');
                }
            })
            ->get();

        $allDocketNumbers = collect();
        foreach ($lorryReceipts as $lr) {
            $bilties = array_map('trim', explode(',', (string) $lr->received_no_bilties));
            $bilties = array_unique(array_filter($bilties));

            if (!empty(array_intersect($consignmentNumbers, $bilties))) {
                // Get ALL docket numbers from those Lorry Receipts
                $allDocketNumbers = $allDocketNumbers->merge($bilties);
            }
        }

        $allDocketNumbers = $allDocketNumbers->unique()->toArray();

        if (!empty($allDocketNumbers)) {
            // Recalculate ALL dockets in the Challan
            $this->calculationService->recalculateForDocketNumbers($allDocketNumbers, $invoice->company_id);
        }
    }
}
