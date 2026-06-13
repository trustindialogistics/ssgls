<?php

namespace App\Listeners;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\InvoiceItem;
use App\Services\LrReceiptCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateOnInvoiceItemDeleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected LrReceiptCalculationService $calculationService) {}

    public function handle(InvoiceItem $item): void
    {
        $consignmentNumber = $item->consignment_number;
        $invoice = $item->invoice;

        if (!$consignmentNumber || !$invoice || $invoice->template_name !== Invoice::TEMPLATE_OFFICE_INVOICE) {
            return;
        }

        // Find all Lorry Receipts containing this consignment number
        $lorryReceipts = LorryReceipt::query()
            ->where('company_id', $invoice->company_id)
            ->where(function ($q) use ($consignmentNumber) {
                $q->where('received_no_bilties', 'like', $consignmentNumber)
                  ->orWhere('received_no_bilties', 'like', $consignmentNumber . ',%')
                  ->orWhere('received_no_bilties', 'like', '%,' . $consignmentNumber)
                  ->orWhere('received_no_bilties', 'like', '%,' . $consignmentNumber . ',%');
            })
            ->get();

        $allDocketNumbers = collect();
        foreach ($lorryReceipts as $lr) {
            $bilties = array_map('trim', explode(',', $lr->received_no_bilties));
            $bilties = array_unique(array_filter($bilties));
            $allDocketNumbers = $allDocketNumbers->merge($bilties);
        }

        $allDocketNumbers = $allDocketNumbers->unique()->values()->toArray();

        if (!empty($allDocketNumbers)) {
            $this->calculationService->recalculateForDocketNumbers($allDocketNumbers, $invoice->company_id);
        }
    }
}
