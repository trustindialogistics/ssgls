<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\InvoiceItem;
use App\Models\CompanySetting;
use App\Models\Payment;
use Carbon\Carbon;

class LrReceiptCalculationService
{
    /**
     * Calculate values for a single LR Receipt Record.
     */
    public function calculateValues(Invoice $lrReceiptRecord): ?array
    {
        if ($lrReceiptRecord->template_name !== Invoice::TEMPLATE_LR_RECEIPT) {
            return null;
        }

        $docketNumber = trim($lrReceiptRecord->invoice_number);
        if ($docketNumber === '') {
            return null;
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
            return [
                'amount_debit' => 0.00,
                'amount_credit' => 0.00,
                'amount_debit_date' => null,
                'amount_credit_date' => null,
                'lorry_receipt_id' => null,
            ];
        }

        $amountCredit = $this->calculateAmountCredit($docketNumber, $companyId);
        $amountDebit = $this->calculateAmountDebit($docketNumber, $companyId, $matchedLorryReceipts);
        $debitDate = $this->calculateDebitDate($docketNumber, $companyId, $matchedLorryReceipts);
        $creditDate = $this->calculateCreditDate($docketNumber, $companyId);
        $lorryReceiptId = $matchedLorryReceipts->first()->id;

        return [
            'amount_debit' => $amountDebit,
            'amount_credit' => $amountCredit,
            'amount_debit_date' => $debitDate,
            'amount_credit_date' => $creditDate,
            'lorry_receipt_id' => $lorryReceiptId,
        ];
    }

    /**
     * Recalculate financial columns for a single LR Receipt Record.
     */
    public function recalculate(Invoice $lrReceiptRecord): void
    {
        $values = $this->calculateValues($lrReceiptRecord);
        if ($values !== null) {
            $lrReceiptRecord->update($values);

            // Recalculate parent Lorry Receipt status
            $companyId = $lrReceiptRecord->company_id;
            $docketNumber = trim($lrReceiptRecord->invoice_number);

            $matchedLorryReceipts = LorryReceipt::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where(function ($q) use ($docketNumber) {
                    $q->where('received_no_bilties', 'like', $docketNumber)
                      ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                      ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                      ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
                })
                ->get();

            foreach ($matchedLorryReceipts as $lr) {
                $lorryInvoice = Invoice::where('company_id', $lr->company_id)
                    ->where('template_name', Invoice::TEMPLATE_LORRY_RECEIPT)
                    ->where(function ($q) use ($lr) {
                        $q->where('invoice_number', $lr->challan_no)
                          ->orWhere('invoice_number', $lr->contract_no);
                    })
                    ->first();
                if ($lorryInvoice) {
                    $lorryInvoice->updateLorryReceiptStatus();
                }
            }
        }
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

        $updates = [];
        foreach ($records as $record) {
            $values = $this->calculateValues($record);
            if ($values !== null) {
                $updates[] = array_merge(['id' => $record->id], $values);
            }
        }

        if (!empty($updates)) {
            Invoice::upsert(
                $updates,
                ['id'],
                ['amount_debit', 'amount_credit', 'amount_debit_date', 'amount_credit_date', 'lorry_receipt_id']
            );

            // Update status of all affected lorry receipts
            $matchedLorryReceipts = LorryReceipt::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where(function ($q) use ($docketNumbers) {
                    foreach ($docketNumbers as $index => $docketNumber) {
                        if ($index === 0) {
                            $q->where('received_no_bilties', 'like', $docketNumber)
                              ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                              ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                              ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
                        } else {
                            $q->orWhere('received_no_bilties', 'like', $docketNumber)
                              ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                              ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                              ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
                        }
                    }
                })
                ->get();

            foreach ($matchedLorryReceipts as $lr) {
                $lorryInvoice = Invoice::where('company_id', $lr->company_id)
                    ->where('template_name', Invoice::TEMPLATE_LORRY_RECEIPT)
                    ->where(function ($q) use ($lr) {
                        $q->where('invoice_number', $lr->challan_no)
                          ->orWhere('invoice_number', $lr->contract_no);
                    })
                    ->first();
                if ($lorryInvoice) {
                    $lorryInvoice->updateLorryReceiptStatus();
                }
            }
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
        $hasFinal = Invoice::lorryReceiptHasFinalPaymentOperation($lorryReceipt);
        $netAmountPayable = $hasFinal ? Invoice::numericTransportAmount($lorryReceipt->net_amount_payable) : 0;
        $totalDebit = Invoice::numericTransportAmount($lorryReceipt->advance_amount) + $netAmountPayable;

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

        foreach ($matchedLorryReceipts as $lorryReceipt) {
            if (Invoice::numericTransportAmount($lorryReceipt->advance_amount) > 0 && $lorryReceipt->advance_on) {
                $dates[] = Carbon::parse($lorryReceipt->advance_on)->translatedFormat($dateFormat);
            }

            if (Invoice::lorryReceiptHasFinalPaymentOperation($lorryReceipt) && $lorryReceipt->final_balance_on) {
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
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $companyId);

        $paymentDates = Payment::query()
            ->whereHas('invoice', function ($q) use ($docketNumber, $companyId) {
                $q->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
                  ->when($companyId, fn($q2) => $q2->where('company_id', $companyId))
                  ->whereHas('items', function ($q3) use ($docketNumber) {
                      $q3->where('consignment_number', $docketNumber);
                  });
            })
            ->whereNotNull('payment_date')
            ->pluck('payment_date');

        if ($paymentDates->isEmpty()) {
            return null;
        }

        $dates = $paymentDates
            ->map(fn($date) => Carbon::parse($date)->translatedFormat($dateFormat))
            ->unique()
            ->values()
            ->toArray();

        return implode(', ', $dates);
    }
}
