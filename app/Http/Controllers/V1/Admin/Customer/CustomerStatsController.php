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

        if ($isDaily) {
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $totalEndDate = Carbon::now()->endOfMonth()->endOfDay();

            $start = $startDate->copy();

            while ($start->lte($totalEndDate)) {
                $bucketStart = $start->copy()->startOfDay();
                $bucketEnd = $start->copy()->endOfDay();

                $lrReceiptAmounts = $this->getCustomerLrAmountsBetween($bucketStart, $bucketEnd, (int) $customer->id, $companyId);

                $debit = $lrReceiptAmounts['debit'];
                $credit = $lrReceiptAmounts['credit'];
                $profit_loss = $lrReceiptAmounts['profit_loss'];

                $expense = Expense::whereBetween(
                    'expense_date',
                    [$bucketStart->format('Y-m-d'), $bucketEnd->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->whereUser($customer->id)
                    ->sum('base_amount');

                $invoiceTotals[] = $credit;
                $expenseTotals[] = $debit;
                $receiptTotals[] = $profit_loss;
                $netProfits[] = $profit_loss - $expense;

                $months[] = $start->format('d');
                $start->addDay();
            }
        } else {
            $monthCounter = 0;
            $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
            $startDate = Carbon::now()->startOfDay();
            $start = Carbon::now();
            $end = Carbon::now();
            $rangeEndDate = null;
            $terms = explode('-', $fiscalYear);
            $companyStartMonth = intval($terms[0] ?? 1) ?: 1;
            $hasCustomRange = $request->filled(['from_date', 'to_date']);

            if ($companyStartMonth <= $start->month) {
                $startDate->month($companyStartMonth)->startOfMonth();
                $start->month($companyStartMonth)->startOfMonth();
                $end->month($companyStartMonth)->endOfMonth();
            } else {
                $startDate->subYear()->month($companyStartMonth)->startOfMonth();
                $start->subYear()->month($companyStartMonth)->startOfMonth();
                $end->subYear()->month($companyStartMonth)->endOfMonth();
            }

            if ($request->has('previous_year')) {
                $startDate->subYear()->startOfMonth();
                $start->subYear()->startOfMonth();
                $end->subYear()->endOfMonth();
            }

            if ($hasCustomRange) {
                $startDate = Carbon::parse($request->from_date)->startOfDay();
                $rangeEndDate = Carbon::parse($request->to_date)->endOfDay();
                $start = $startDate->copy()->startOfMonth();
                $end = $startDate->copy()->endOfMonth();
            }

            while ($hasCustomRange ? $start->lte($rangeEndDate) : $monthCounter < 12) {
                $bucketStart = $hasCustomRange && $start->lt($startDate) ? $startDate->copy() : $start->copy();
                $bucketEnd = $hasCustomRange && $end->gt($rangeEndDate) ? $rangeEndDate->copy() : $end->copy();

                $lrReceiptAmounts = $this->getCustomerLrAmountsBetween($bucketStart, $bucketEnd, (int) $customer->id, $companyId);

                $debit = $lrReceiptAmounts['debit'];
                $credit = $lrReceiptAmounts['credit'];
                $profit_loss = $lrReceiptAmounts['profit_loss'];

                $expense = Expense::whereBetween(
                    'expense_date',
                    [$bucketStart->format('Y-m-d'), $bucketEnd->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->whereUser($customer->id)
                    ->sum('base_amount');

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

            $totalEndDate = $hasCustomRange ? $rangeEndDate : $start->copy()->subMonth()->endOfMonth();
        }

        $salesTotal = Invoice::whereBetween(
            'invoice_date',
            [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->whereRegularInvoice()
            ->sum('base_total');

        // Consignment Profit/Loss is the sum of profit/loss of all customer's LRs in that date range
        $chartLrReceipts = Invoice::whereCompany()
            ->whereCustomer($customer->id)
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereBetween('invoice_date', [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')])
            ->get();

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($chartLrReceipts as $lr) {
            $totalDebit += $lr->amountDebit;
            $totalCredit += $lr->amountCredit;
        }
        $totalReceipts = $totalCredit - $totalDebit;

        $totalExpenses = Expense::whereBetween(
            'expense_date',
            [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereUser($customer->id)
            ->sum('base_amount');

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
