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
        $cursor = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $bucketStart = $cursor->copy()->startOfMonth();
            $bucketEnd = $cursor->copy()->endOfMonth();

            if ($bucketStart->lessThan($startDate)) {
                $bucketStart = $startDate->copy();
            }

            if ($bucketEnd->greaterThan($endDate)) {
                $bucketEnd = $endDate->copy();
            }

            array_push(
                $invoiceTotals,
                Invoice::whereBetween(
                    'invoice_date',
                    [$bucketStart->format('Y-m-d'), $bucketEnd->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->whereCustomer($customer->id)
                    ->sum('base_total') ?? 0
            );
            array_push(
                $expenseTotals,
                Expense::whereBetween(
                    'expense_date',
                    [$bucketStart->format('Y-m-d'), $bucketEnd->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->whereUser($customer->id)
                    ->sum('base_amount') ?? 0
            );
            array_push(
                $receiptTotals,
                Payment::whereBetween(
                    'payment_date',
                    [$bucketStart->format('Y-m-d'), $bucketEnd->format('Y-m-d')]
                )
                    ->whereCompany()
                    ->whereCustomer($customer->id)
                    ->sum('base_amount') ?? 0
            );

            $i = count($receiptTotals) - 1;
            array_push(
                $netProfits,
                ($receiptTotals[$i] - $expenseTotals[$i])
            );
            array_push($months, $cursor->translatedFormat('M y'));
            $cursor->addMonth()->startOfMonth();
        }

        $salesTotal = Invoice::whereBetween(
            'invoice_date',
            [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->sum('base_total');
        $totalReceipts = Payment::whereBetween(
            'payment_date',
            [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
        )
            ->whereCompany()
            ->whereCustomer($customer->id)
            ->sum('base_amount');
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
