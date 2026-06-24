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

        return $this->calculateValuesWithLorryReceipts($lrReceiptRecord, $matchedLorryReceipts);
    }

    /**
     * Calculate values for a single LR Receipt Record using already matched/loaded LorryReceipts.
     */
    public function calculateValuesWithLorryReceipts(Invoice $lrReceiptRecord, $matchedLorryReceipts): ?array
    {
        if ($lrReceiptRecord->template_name !== Invoice::TEMPLATE_LR_RECEIPT) {
            return null;
        }

        $docketNumber = trim($lrReceiptRecord->invoice_number);
        if ($docketNumber === '') {
            return null;
        }

        $companyId = $lrReceiptRecord->company_id;

        // Check if there is an office invoice with this consignment number
        $hasOfficeInvoice = Invoice::query()
            ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereHas('items', function ($q) use ($docketNumber) {
                $q->where('consignment_number', $docketNumber);
            })
            ->exists();

        $amountCredit = 0.00;
        $creditDate = null;

        if ($hasOfficeInvoice) {
            $amountCredit = $this->calculateAmountCredit($docketNumber, $companyId);
            $creditDate = $this->calculateCreditDate($docketNumber, $companyId);
        }

        // If there are no matched lorry receipts, debit is 0, but credit remains calculated
        if ($matchedLorryReceipts->isEmpty()) {
            return [
                'amount_debit' => 0.00,
                'amount_credit' => $amountCredit,
                'amount_debit_date' => null,
                'amount_credit_date' => $creditDate,
                'lorry_receipt_id' => null,
            ];
        }

        $amountDebit = $this->calculateAmountDebit($docketNumber, $companyId, $matchedLorryReceipts);
        $debitDate = $this->calculateDebitDate($docketNumber, $companyId, $matchedLorryReceipts);
        $lorryReceiptId = $matchedLorryReceipts->first()->id;

        return [
            'amount_debit' => $amountDebit,
            'amount_credit' => $amountCredit,
            'amount_debit_date' => $debitDate,
            'amount_credit_date' => $creditDate,
            'lorry_receipt_id' => $lorryReceiptId,
        ];
    }

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

        // Query LorryReceipts ONCE
        $matchedLorryReceipts = LorryReceipt::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($docketNumber) {
                $q->where('received_no_bilties', 'like', $docketNumber)
                  ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                  ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                  ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
            })
            ->get();

        // Use already-loaded LorryReceipts (no second query)
        $values = $this->calculateValuesWithLorryReceipts($lrReceiptRecord, $matchedLorryReceipts);

        if ($values !== null) {
            $lrReceiptRecord->update($values);

            // Reuse already-loaded $matchedLorryReceipts for status update
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

        // Query LorryReceipts ONCE for all dockets
        $allLorryReceipts = LorryReceipt::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($docketNumbers) {
                foreach ($docketNumbers as $docketNumber) {
                    $q->orWhere('received_no_bilties', 'like', $docketNumber)
                      ->orWhere('received_no_bilties', 'like', $docketNumber . ',%')
                      ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber)
                      ->orWhere('received_no_bilties', 'like', '%,' . $docketNumber . ',%');
                }
            })
            ->get();

        $records = Invoice::where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereIn('invoice_number', $docketNumbers)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        $updates = [];
        foreach ($records as $record) {
            // Filter LorryReceipts that contain this docket (from already-loaded collection)
            $matchedLorryReceipts = $allLorryReceipts->filter(function ($lr) use ($record) {
                $bilties = array_map('trim', explode(',', (string) $lr->received_no_bilties));
                $bilties = array_unique(array_filter($bilties));
                return in_array(trim($record->invoice_number), $bilties);
            });

            $values = $this->calculateValuesWithLorryReceipts($record, $matchedLorryReceipts);
            if ($values !== null) {
                $updates[] = array_merge(['id' => $record->id], $values);
            }
        }

        if (!empty($updates)) {
            foreach ($updates as $update) {
                $id = $update['id'];
                unset($update['id']);
                Invoice::where('id', $id)->update($update);
            }

            // Status update uses $allLorryReceipts already loaded
            foreach ($allLorryReceipts as $lr) {
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

        $dockets = array_map('trim', explode(',', (string) $lorryReceipt->received_no_bilties));
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
            $count = count($dockets);
            if ($count === 0) {
                return 0.0;
            }
            if (!in_array($docketNumber, $dockets)) {
                return 0.0;
            }
            
            // If there is only a single docket, it gets the full debit amount
            if ($count === 1) {
                return $totalDebit;
            }
            
            // If there are multiple dockets, return 0.00 since we don't know the split yet
            return 0.0;
        }

        // Check if the docket is in the list
        if (!in_array($docketNumber, $dockets)) {
            return 0.0;
        }

        // Filter dockets to only active ones (credits > 0)
        $activeDockets = [];
        foreach ($dockets as $doc) {
            if (($credits[$doc] ?? 0.0) > 0.0) {
                $activeDockets[] = $doc;
            }
        }

        // If this specific docket has no credit, it gets 0.00
        if (!in_array($docketNumber, $activeDockets)) {
            return 0.0;
        }

        $lastActiveDocket = end($activeDockets);

        if ($docketNumber !== $lastActiveDocket) {
            return round($totalDebit * ($credits[$docketNumber] / $totalCredit), 2);
        }

        // If it is the last active docket, calculate sum of all other active dockets and assign remainder
        $sumOfOthers = 0.0;
        foreach ($activeDockets as $doc) {
            if ($doc === $lastActiveDocket) {
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
