<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\InvoiceItem;
use App\Models\CompanySetting;
use Carbon\Carbon;

class LrReceiptCalculationService
{
    /**
     * Recalculate financial columns for a single LR Receipt Record.
     */
    public function recalculate(Invoice $lrReceiptRecord): void
    {
        if ($lrReceiptRecord->template_name !== Invoice::TEMPLATE_LR_RECEIPT) {
            return;
        }

        $docketNumber = trim($lrReceiptRecord->invoice_number);
        if ($docketNumber === '') {
            return;
        }

        $companyId = $lrReceiptRecord->company_id;

        // Fetch matching lorry receipts using SQL search to avoid loading all records
        $matchedLorryReceipts = LorryReceipt::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($docketNumber) {
                $q->where('received_no_bilties', 'like', $docketNumber)
                  ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                  ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                  ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
            })
            ->get();

        // Check if there is an office invoice with this consignment number
        $hasOfficeInvoice = Invoice::query()
            ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereHas('items', function ($q) use ($docketNumber) {
                $q->where('consignment_number', $docketNumber);
            })
            ->exists();

        // Golden Rule: Both conditions must be met, else everything is 0/null
        if ($matchedLorryReceipts->isEmpty() || !$hasOfficeInvoice) {
            $lrReceiptRecord->update([
                'amount_debit' => 0.00,
                'amount_credit' => 0.00,
                'amount_debit_date' => null,
                'amount_credit_date' => null,
                'lorry_receipt_id' => null,
            ]);
            return;
        }

        // Calculate values
        $amountCredit = $this->calculateAmountCredit($docketNumber, $companyId);
        $amountDebit = $this->calculateAmountDebit($docketNumber, $companyId, $matchedLorryReceipts);
        $debitDate = $this->calculateDebitDate($docketNumber, $companyId, $matchedLorryReceipts);
        $creditDate = $this->calculateCreditDate($docketNumber, $companyId);
        $lorryReceiptId = $matchedLorryReceipts->first()->id;

        $lrReceiptRecord->update([
            'amount_debit' => $amountDebit,
            'amount_credit' => $amountCredit,
            'amount_debit_date' => $debitDate,
            'amount_credit_date' => $creditDate,
            'lorry_receipt_id' => $lorryReceiptId,
        ]);
    }

    /**
     * Recalculate financial columns for a list of docket numbers.
     */
    public function recalculateForDocketNumbers(array $docketNumbers, ?int $companyId = null): void
    {
        $docketNumbers = array_map('trim', $docketNumbers);
        $docketNumbers = array_unique(array_filter($docketNumbers));

        if (empty($docketNumbers)) {
            return;
        }

        $records = Invoice::where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereIn('invoice_number', $docketNumbers)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        foreach ($records as $record) {
            $this->recalculate($record);
        }
    }

    /**
     * Calculate AMOUNT CREDITED for a docket number.
     */
    public function calculateAmountCredit(string $docketNumber, ?int $companyId): float
    {
        return (float) InvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($companyId) {
                $q->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
                  ->when($companyId, fn($q2) => $q2->where('company_id', $companyId));
            })
            ->where('consignment_number', $docketNumber)
            ->sum('price');
    }

    /**
     * Calculate AMOUNT DEBIT for a docket number (Proportional Allocation).
     */
    public function calculateAmountDebit(string $docketNumber, ?int $companyId, $matchedLorryReceipts): float
    {
        $totalDebitShare = 0.0;

        foreach ($matchedLorryReceipts as $lr) {
            $totalDebitShare += $this->calculateDebitShareForLorryReceipt($lr, $docketNumber, $companyId);
        }

        return $totalDebitShare;
    }

    /**
     * Calculate the debit share of a specific Lorry Receipt for a docket number.
     */
    public function calculateDebitShareForLorryReceipt(LorryReceipt $lorryReceipt, string $docketNumber, ?int $companyId): float
    {
        $invoiceModel = new Invoice();
        
        $hasFinal = $invoiceModel->lorryReceiptHasFinalPaymentOperation($lorryReceipt);
        $netAmountPayable = $hasFinal ? $invoiceModel->numericTransportAmount($lorryReceipt->net_amount_payable) : 0;
        $totalDebit = $invoiceModel->numericTransportAmount($lorryReceipt->advance_amount) + $netAmountPayable;

        $dockets = array_map('trim', explode(',', $lorryReceipt->received_no_bilties));
        $dockets = array_unique(array_filter($dockets));
        sort($dockets); // Sort ascending

        // Calculate total credit for all dockets in this lorry receipt in a single query
        $docketsCredit = InvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($companyId) {
                $q->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
                  ->when($companyId, fn($q2) => $q2->where('company_id', $companyId));
            })
            ->whereIn('consignment_number', $dockets)
            ->groupBy('consignment_number')
            ->selectRaw('consignment_number, sum(price) as total_price')
            ->pluck('total_price', 'consignment_number')
            ->all();

        $credits = [];
        $totalCredit = 0.0;
        foreach ($dockets as $doc) {
            $credit = (float) ($docketsCredit[$doc] ?? 0.0);
            $credits[$doc] = $credit;
            $totalCredit += $credit;
        }

        if ($totalCredit == 0.0) {
            return 0.0;
        }

        // Check if the docket is in the list
        if (!in_array($docketNumber, $dockets)) {
            return 0.0;
        }

        $lastDocket = end($dockets);

        if ($docketNumber !== $lastDocket) {
            return round($totalDebit * ($credits[$docketNumber] / $totalCredit), 2);
        }

        // If it is the last docket, calculate sum of all others and assign remainder
        $sumOfOthers = 0.0;
        foreach ($dockets as $doc) {
            if ($doc === $lastDocket) {
                continue;
            }
            $sumOfOthers += round($totalDebit * ($credits[$doc] / $totalCredit), 2);
        }

        return round($totalDebit - $sumOfOthers, 2);
    }

    /**
     * Calculate AMOUNT DEBIT DATE.
     */
    public function calculateDebitDate(string $docketNumber, ?int $companyId, $matchedLorryReceipts): ?string
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $companyId);
        $dates = [];
        $invoiceModel = new Invoice();

        foreach ($matchedLorryReceipts as $lorryReceipt) {
            if ($invoiceModel->numericTransportAmount($lorryReceipt->advance_amount) > 0 && $lorryReceipt->advance_on) {
                $dates[] = Carbon::parse($lorryReceipt->advance_on)->translatedFormat($dateFormat);
            }

            if ($invoiceModel->lorryReceiptHasFinalPaymentOperation($lorryReceipt) && $lorryReceipt->final_balance_on) {
                $dates[] = Carbon::parse($lorryReceipt->final_balance_on)->translatedFormat($dateFormat);
            }
        }

        if (empty($dates)) {
            return null;
        }

        return implode(', ', array_unique($dates));
    }

    /**
     * Calculate AMOUNT CREDIT DATE.
     */
    public function calculateCreditDate(string $docketNumber, ?int $companyId): ?string
    {
        $matchingInvoices = Invoice::query()
            ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereHas('items', function ($q) use ($docketNumber) {
                $q->where('consignment_number', $docketNumber);
            })
            ->with('payments')
            ->get();

        $dates = [];
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $companyId);

        foreach ($matchingInvoices as $matchingInvoice) {
            foreach ($matchingInvoice->payments as $payment) {
                if ($payment->payment_date) {
                    $dates[] = Carbon::parse($payment->payment_date)->translatedFormat($dateFormat);
                }
            }
        }

        if (empty($dates)) {
            return null;
        }

        return implode(', ', array_unique($dates));
    }
}
