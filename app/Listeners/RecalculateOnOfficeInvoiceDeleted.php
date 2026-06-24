<?php

namespace App\Listeners;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Services\LrReceiptCalculationService;

class RecalculateOnOfficeInvoiceDeleted
{
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

        $consignmentNumbers = $invoice->items()->pluck('consignment_number')->filter()->map(fn($v) => trim($v))->unique()->toArray();

        if (empty($consignmentNumbers)) {
            return;
        }

        // Find matching Lorry Receipts using SQL search
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

        $allDocketNumbers = collect($consignmentNumbers); // Also include the consignment numbers themselves to ensure they are recalculated to 0

        foreach ($lorryReceipts as $lr) {
            $bilties = array_map('trim', explode(',', (string) $lr->received_no_bilties));
            $bilties = array_unique(array_filter($bilties));
            $allDocketNumbers = $allDocketNumbers->merge($bilties);
        }

        $allDocketNumbers = $allDocketNumbers->unique()->toArray();

        if (!empty($allDocketNumbers)) {
            $this->calculationService->recalculateForDocketNumbers($allDocketNumbers, $invoice->company_id);
        }
    }
}
