<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerStatsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Response
     */
    public function __invoke(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        $months = [];
        $invoiceTotals = [];
        $expenseTotals = [];
        $receiptTotals = [];
        $netProfits = [];

        $isDaily = $request->get('view_type') === 'day';
        $companyId = (int) $request->header('company');

        // 1. Establish date ranges
        if ($isDaily) {
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $totalEndDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
            $terms = explode('-', $fiscalYear);
            $companyStartMonth = intval($terms[0] ?? 1) ?: 1;
            $hasCustomRange = $request->filled(['from_date', 'to_date']);

            $start = Carbon::now();
            if ($companyStartMonth <= $start->month) {
                $startDate->month($companyStartMonth)->startOfMonth();
            } else {
                $startDate->subYear()->month($companyStartMonth)->startOfMonth();
            }

            if ($request->has('previous_year')) {
                $startDate->subYear()->startOfMonth();
            }

            if ($hasCustomRange) {
                $startDate = Carbon::parse($request->from_date)->startOfDay();
                $totalEndDate = Carbon::parse($request->to_date)->endOfDay();
            } else {
                $startTemp = $startDate->copy();
                $monthCounterTemp = 0;
                while ($monthCounterTemp < 12) {
                    $monthCounterTemp++;
                    $startTemp->addMonth();
                }
                $totalEndDate = $startTemp->subMonth()->endOfMonth();
            }
        }

        // 2. Pre-fetch all LRs and Expenses in memory
        $lrReceipts = Invoice::where('company_id', $companyId)
            ->whereCustomer($customer->id)
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereBetween('invoice_date', [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')])
            ->get();

        $expenses = Expense::whereBetween(
            'expense_date',
            [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereUser($customer->id)
            ->get();

        // 3. Process daily/monthly buckets
        if ($isDaily) {
            $start = $startDate->copy();

            while ($start->lte($totalEndDate)) {
                $bucketStartStr = $start->format('Y-m-d');

                // Filter in memory
                $bucketLrs = $lrReceipts->filter(function ($lr) use ($bucketStartStr) {
                    $dateStr = $lr->invoice_date instanceof Carbon ? $lr->invoice_date->format('Y-m-d') : Carbon::parse($lr->invoice_date)->format('Y-m-d');
                    return $dateStr === $bucketStartStr;
                });

                $debit = 0;
                $credit = 0;
                foreach ($bucketLrs as $lr) {
                    $debit += $lr->amountDebit;
                    $credit += $lr->amountCredit;
                }
                $profit_loss = $credit - $debit;

                $expense = $expenses->filter(function ($exp) use ($bucketStartStr) {
                    $dateStr = $exp->expense_date instanceof Carbon ? $exp->expense_date->format('Y-m-d') : Carbon::parse($exp->expense_date)->format('Y-m-d');
                    return $dateStr === $bucketStartStr;
                })->sum('base_amount');

                $invoiceTotals[] = $credit;
                $expenseTotals[] = $debit;
                $receiptTotals[] = $profit_loss;
                $netProfits[] = $profit_loss - $expense;

                $months[] = $start->format('d');
                $start->addDay();
            }
        } else {
            $monthCounter = 0;
            $start = $startDate->copy();
            $end = $startDate->copy()->endOfMonth();
            $hasCustomRange = $request->filled(['from_date', 'to_date']);

            while ($hasCustomRange ? $start->lte($totalEndDate) : $monthCounter < 12) {
                $bucketStart = $hasCustomRange && $start->lt($startDate) ? $startDate->copy() : $start->copy();
                $bucketEnd = $hasCustomRange && $end->gt($totalEndDate) ? $totalEndDate->copy() : $end->copy();

                $bucketStartStr = $bucketStart->format('Y-m-d');
                $bucketEndStr = $bucketEnd->format('Y-m-d');

                // Filter in memory
                $bucketLrs = $lrReceipts->filter(function ($lr) use ($bucketStartStr, $bucketEndStr) {
                    $dateStr = $lr->invoice_date instanceof Carbon ? $lr->invoice_date->format('Y-m-d') : Carbon::parse($lr->invoice_date)->format('Y-m-d');
                    return $dateStr >= $bucketStartStr && $dateStr <= $bucketEndStr;
                });

                $debit = 0;
                $credit = 0;
                foreach ($bucketLrs as $lr) {
                    $debit += $lr->amountDebit;
                    $credit += $lr->amountCredit;
                }
                $profit_loss = $credit - $debit;

                $expense = $expenses->filter(function ($exp) use ($bucketStartStr, $bucketEndStr) {
                    $dateStr = $exp->expense_date instanceof Carbon ? $exp->expense_date->format('Y-m-d') : Carbon::parse($exp->expense_date)->format('Y-m-d');
                    return $dateStr >= $bucketStartStr && $dateStr <= $bucketEndStr;
                })->sum('base_amount');

                $invoiceTotals[] = $credit;
                $expenseTotals[] = $debit;
                $receiptTotals[] = $profit_loss;
                $netProfits[] = $profit_loss - $expense;

                $months[] = $hasCustomRange ? $start->translatedFormat('M y') : $start->translatedFormat('M');
                $monthCounter++;
                $end->startOfMonth();
                $start->addMonth()->startOfMonth();
                $end->addMonth()->endOfMonth();
            }
        }

        // 4. Calculate overall totals using pre-fetched collections
        $salesTotal = Invoice::whereBetween(
            'invoice_date',
            [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->whereRegularInvoice()
            ->sum('base_total');

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($lrReceipts as $lr) {
            $totalDebit += $lr->amountDebit;
            $totalCredit += $lr->amountCredit;
        }
        $totalReceipts = $totalCredit - $totalDebit;

        $totalExpenses = $expenses->sum('base_amount');

        $netProfit = (int) $totalReceipts - (int) $totalExpenses;

        $chartData = [
            'months' => $months,
            'invoiceTotals' => $invoiceTotals,
            'expenseTotals' => $expenseTotals,
            'receiptTotals' => $receiptTotals,
            'netProfit' => $netProfit,
            'netProfits' => $netProfits,
            'salesTotal' => $salesTotal,
            'totalReceipts' => $totalReceipts,
            'totalExpenses' => $totalExpenses,
        ];

        $officeInvoices = Invoice::with('customer.currency')
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
            ->orderBy('invoice_date', 'desc')
            ->limit(25)
            ->get();

        $lrReceipts = Invoice::with('customer.currency')
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->orderBy('invoice_date', 'desc')
            ->limit(25)
            ->get();

        $payments = Payment::with(['customer.currency', 'invoice', 'paymentMethod'])
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->orderBy('payment_date', 'desc')
            ->limit(25)
            ->get();

        $customer = Customer::with(['billingAddress', 'shippingAddress', 'currency', 'fields.customField'])
            ->find($customer->id);

        return (new CustomerResource($customer))
            ->additional(['meta' => [
                'chartData' => $chartData,
                'activity' => [
                    'invoices' => InvoiceResource::collection($officeInvoices),
                    'lrReceipts' => InvoiceResource::collection($lrReceipts),
                    'payments' => PaymentResource::collection($payments),
                ],
            ]]);
    }

    /**
     * @return array{debit: int|float, credit: int|float, profit_loss: int|float}
     */
    private function getCustomerLrAmountsBetween(Carbon $start, Carbon $end, int $customerId, int $companyId): array
    {
        $lrReceipts = Invoice::where('company_id', $companyId)
            ->whereCustomer($customerId)
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereBetween('invoice_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get();

        $debit = 0;
        $credit = 0;

        foreach ($lrReceipts as $lrReceipt) {
            $debit += $lrReceipt->amountDebit;
            $credit += $lrReceipt->amountCredit;
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'profit_loss' => $credit - $debit,
        ];
    }
}
