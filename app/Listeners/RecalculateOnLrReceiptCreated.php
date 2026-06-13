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

class RecalculateOnLrReceiptCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected LrReceiptCalculationService $calculationService) {}

    public function handle(Invoice $invoice): void
    {
        if ($invoice->template_name !== Invoice::TEMPLATE_LR_RECEIPT) {
            return;
        }

        $docketNumber = trim($invoice->invoice_number);
        if ($docketNumber === '') {
            return;
        }

        // Find matching Lorry Receipt
        $lorryReceipt = LorryReceipt::query()
            ->where('company_id', $invoice->company_id)
            ->where(function ($q) use ($docketNumber) {
                $q->where('received_no_bilties', 'like', $docketNumber)
                  ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                  ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                  ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
            })
            ->first();

        if ($lorryReceipt) {
            $invoice->update(['lorry_receipt_id' => $lorryReceipt->id]);
        }

        // Recalculate financial columns
        $this->calculationService->recalculate($invoice);
    }
}
