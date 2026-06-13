<?php

namespace App\Listeners;

use App\Models\Payment;
use App\Models\Invoice;
use App\Services\LrReceiptCalculationService;

class RecalculateOnPaymentDeleted
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
    public function handle(Payment $payment): void
    {
        $invoice = $payment->invoice;
        if (!$invoice || $invoice->template_name !== Invoice::TEMPLATE_OFFICE_INVOICE) {
            return;
        }

        $invoice->loadMissing('items');
        $consignmentNumbers = $invoice->items()->pluck('consignment_number')->filter()->map(fn($v) => trim($v))->unique()->toArray();

        if (empty($consignmentNumbers)) {
            return;
        }

        $lrReceiptRecords = Invoice::where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereIn('invoice_number', $consignmentNumbers)
            ->where('company_id', $payment->company_id)
            ->get();

        foreach ($lrReceiptRecords as $record) {
            $creditDate = $this->calculationService->calculateCreditDate(trim($record->invoice_number), $record->company_id);
            $record->update(['amount_credit_date' => $creditDate]);
        }
    }
}
