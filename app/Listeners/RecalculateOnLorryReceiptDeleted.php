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

class RecalculateOnLorryReceiptDeleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected LrReceiptCalculationService $calculationService) {}

    public function handle(LorryReceipt $lorryReceipt): void
    {
        $dockets = array_map('trim', explode(',', (string) $lorryReceipt->received_no_bilties));
        $dockets = array_unique(array_filter($dockets));

        if (empty($dockets)) {
            return;
        }

        // Nullify lorry_receipt_id for affected records
        Invoice::where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereIn('invoice_number', $dockets)
            ->where('company_id', $lorryReceipt->company_id)
            ->where('lorry_receipt_id', $lorryReceipt->id)
            ->update(['lorry_receipt_id' => null]);

        // Recalculate
        $this->calculationService->recalculateForDocketNumbers($dockets, $lorryReceipt->company_id);
    }
}
