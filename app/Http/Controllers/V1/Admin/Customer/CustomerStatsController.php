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

        [$startDate, $endDate] = $this->resolveDateRange($request);

        // Fetch individual LRs for the trend line
        $lrReceiptsForChart = Invoice::whereCompany()
            ->whereCustomer($customer->id)
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereBetween('invoice_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('invoice_date', 'desc')
            ->limit(25)
            ->get()
            ->reverse()
            ->values();

        foreach ($lrReceiptsForChart as $lrReceipt) {
            $months[] = $lrReceipt->invoice_number;
            $plr = $lrReceipt->amountCredit - $lrReceipt->amountDebit;
            $receiptTotals[] = $plr;
            $invoiceTotals[] = $lrReceipt->amountCredit;
            $expenseTotals[] = $lrReceipt->amountDebit;
            $netProfits[] = $plr;
        }

        $salesTotal = Invoice::whereBetween(
            'invoice_date',
            [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->whereRegularInvoice()
            ->sum('base_total');

        // Consignment Profit/Loss is the sum of profit/loss of all customer's LRs in that date range
        $chartLrReceipts = Invoice::whereCompany()
            ->whereCustomer($customer->id)
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->whereBetween('invoice_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
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
            [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
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

    private function resolveDateRange(Request $request): array
    {
        if ($request->from_date && $request->to_date) {
            return [
                Carbon::createFromFormat('Y-m-d', $request->from_date)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $request->to_date)->endOfDay(),
            ];
        }

        $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company'));
        $terms = explode('-', $fiscalYear);
        $companyStartMonth = intval($terms[0] ?? 1) ?: 1;
        $startDate = Carbon::now()->startOfDay();

        if ($companyStartMonth <= $startDate->month) {
            $startDate->month($companyStartMonth)->startOfMonth();
        } else {
            $startDate->subYear()->month($companyStartMonth)->startOfMonth();
        }

        if ($request->has('previous_year')) {
            $startDate->subYear()->startOfMonth();
        }

        return [$startDate, $startDate->copy()->addMonths(11)->endOfMonth()];
    }
}
