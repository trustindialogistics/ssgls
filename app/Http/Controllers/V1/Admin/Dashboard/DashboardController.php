<?php

namespace App\Http\Controllers\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('view dashboard', $company);

        $debit_totals = [];
        $credit_totals = [];
        $invoice_totals = [];
        $expense_totals = [];
        $receipt_totals = [];
        $net_income_totals = [];

        $months = [];
        $monthCounter = 0;
        $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
        $startDate = Carbon::now();
        $start = Carbon::now();
        $end = Carbon::now();
        $rangeEndDate = null;
        $terms = explode('-', $fiscalYear);
        $companyStartMonth = intval($terms[0]);
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

            $lrReceiptAmounts = $this->lrReceiptAmountsBetween($bucketStart, $bucketEnd, (int) $company->id);

            $debit_totals[] = $lrReceiptAmounts['debit'];
            $credit_totals[] = $lrReceiptAmounts['credit'];
            $invoice_totals[] = $lrReceiptAmounts['profit_loss'];

            array_push(
                $expense_totals,
                Expense::whereBetween(
                    'expense_date',
                    [$bucketStart->format('Y-m-d'), $bucketEnd->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->where(function ($query) {
                        $query->whereNull('invoice_id')
                            ->orWhereHas('invoice', function ($query) {
                                $query->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE);
                            });
                    })
                    ->sum('base_amount')
            );

            $receipt_totals[] = $lrReceiptAmounts['profit_loss'];
            $net_income_totals[] = $lrReceiptAmounts['profit_loss'] - $expense_totals[count($expense_totals) - 1];

            array_push($months, $hasCustomRange ? $start->translatedFormat('M y') : $start->translatedFormat('M'));
            $monthCounter++;
            $end->startOfMonth();
            $start->addMonth()->startOfMonth();
            $end->addMonth()->endOfMonth();
        }

        $totalEndDate = $hasCustomRange ? $rangeEndDate : $start->copy()->subMonth()->endOfMonth();

        $totalLrReceiptAmounts = $this->lrReceiptAmountsBetween($startDate, $totalEndDate, (int) $company->id);
        $total_sales = $totalLrReceiptAmounts['credit'];
        $total_receipts = $totalLrReceiptAmounts['profit_loss'];

        $total_expenses = Expense::whereBetween(
            'expense_date',
            [$startDate->format('Y-m-d'), $totalEndDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->sum('base_amount');

        $total_net_income = (int) $total_receipts - (int) $total_expenses;

        $chart_data = [
            'months' => $months,
            'debit_totals' => $debit_totals,
            'credit_totals' => $credit_totals,
            'invoice_totals' => $invoice_totals,
            'expense_totals' => $expense_totals,
            'receipt_totals' => $receipt_totals,
            'net_income_totals' => $net_income_totals,
        ];

        $total_customer_count = Customer::whereCompany()->count();
        $total_invoice_count = Invoice::whereCompany()
            ->whereRegularInvoice()
            ->count();
        $total_estimate_count = Estimate::whereCompany()->count();
        $total_amount_due = Invoice::whereCompany()
            ->whereRegularInvoice()
            ->sum('base_due_amount');

        $recent_due_invoices = Invoice::with('customer')
            ->whereCompany()
            ->whereRegularInvoice()
            ->where('base_due_amount', '>', 0)
            ->take(5)
            ->latest()
            ->get();
        $recent_estimates = Estimate::with('customer')->whereCompany()->take(5)->latest()->get();

        return response()->json([
            'total_amount_due' => $total_amount_due,
            'total_customer_count' => $total_customer_count,
            'total_invoice_count' => $total_invoice_count,
            'total_estimate_count' => $total_estimate_count,

            'recent_due_invoices' => BouncerFacade::can('view-invoice', Invoice::class) ? $recent_due_invoices : [],
            'recent_estimates' => BouncerFacade::can('view-estimate', Estimate::class) ? $recent_estimates : [],

            'chart_data' => $chart_data,

            'total_sales' => $total_sales,
            'total_receipts' => $total_receipts,
            'total_expenses' => $total_expenses,
            'total_net_income' => $total_net_income,
        ]);
    }

    /**
     * @return array{debit: int|float, credit: int|float, profit_loss: int|float}
     */
    private function lrReceiptAmountsBetween(Carbon $start, Carbon $end, int $companyId): array
    {
        $lrReceipts = Invoice::where('company_id', $companyId)
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
