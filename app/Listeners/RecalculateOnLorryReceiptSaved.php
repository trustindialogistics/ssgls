<?php

namespace App\Listeners;

use App\Models\LorryReceipt;
use App\Models\Invoice;
use App\Services\LrReceiptCalculationService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateOnLorryReceiptSaved implements ShouldQueue
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
    public function handle(LorryReceipt $lorryReceipt): void
    {
        // Always update parent Lorry Receipt status if Invoice exists
        $lorryInvoice = Invoice::where('company_id', $lorryReceipt->company_id)
            ->where('template_name', Invoice::TEMPLATE_LORRY_RECEIPT)
            ->where(function ($q) use ($lorryReceipt) {
                $q->where('invoice_number', $lorryReceipt->challan_no)
                  ->orWhere('invoice_number', $lorryReceipt->contract_no);
            })
            ->first();

        if ($lorryInvoice) {
            $lorryInvoice->updateLorryReceiptStatus();
        }

        $dockets = array_map('trim', explode(',', (string) $lorryReceipt->received_no_bilties));
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
