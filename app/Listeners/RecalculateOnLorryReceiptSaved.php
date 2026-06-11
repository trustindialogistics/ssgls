<?php

namespace App\Listeners;

use App\Models\LorryReceipt;
use App\Models\Invoice;
use App\Services\LrReceiptCalculationService;

class RecalculateOnLorryReceiptSaved
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
    public function handle(LorryReceipt $lorryReceipt): void
    {
        $dockets = array_map('trim', explode(',', $lorryReceipt->received_no_bilties));
        $dockets = array_unique(array_filter($dockets));

        if (empty($dockets)) {
            return;
        }

        // Update lorry_receipt_id on matching LR Receipt Records
        Invoice::where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereIn('invoice_number', $dockets)
            ->where('company_id', $lorryReceipt->company_id)
            ->update(['lorry_receipt_id' => $lorryReceipt->id]);

        $this->calculationService->recalculateForDocketNumbers($dockets, $lorryReceipt->company_id);
    }
}
