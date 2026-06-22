<?php

namespace App\Http\Controllers\V1\Admin\Report;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PDF;

class CustomerSalesReportController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  string  $hash
     * @return JsonResponse
     */
    public function __invoke(Request $request, $hash)
    {
        $company = Company::where('unique_hash', $hash)->first();

        $this->authorize('view report', $company);

        $locale = CompanySetting::getSetting('language', $company->id);

        App::setLocale($locale);

        $start = Carbon::createFromFormat('Y-m-d', $request->from_date);
        $end = Carbon::createFromFormat('Y-m-d', $request->to_date);

        // Fetch all invoices in date range with customer and consignee, optionally filtered by customer name/id
        $invoices = Invoice::with(['customer', 'consigneeCustomer'])
            ->where('company_id', $company->id)
            ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE)
            ->whereBetween('invoice_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->when($request->customer_id, function ($query) use ($request, $company) {
                $customer = Customer::find($request->customer_id);
                if ($customer) {
                    $normalizedName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $customer->name));
                    $allIds = Customer::whereCompany($company->id)
                        ->get(['id', 'name'])
                        ->filter(function ($c) use ($normalizedName) {
                            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $c->name)) === $normalizedName;
                        })
                        ->pluck('id')
                        ->toArray();

                    $query->where(function ($q) use ($allIds) {
                        $q->whereIn('customer_id', $allIds)
                          ->orWhereIn('consignee_customer_id', $allIds);
                    });
                }
            })
            ->when($request->customer_name, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('customer', function ($sub) use ($request) {
                        $sub->where('name', 'LIKE', '%'.$request->customer_name.'%');
                    })->orWhereHas('consigneeCustomer', function ($sub) use ($request) {
                        $sub->where('name', 'LIKE', '%'.$request->customer_name.'%');
                    });
                });
            })
            ->get();

        // Group sales by paying customer (based on GST Tax Through field)
        $customerSales = [];
        $totalAmount = 0;

        foreach ($invoices as $invoice) {
            // Get GST Tax Through custom field value
            $gstTaxThrough = $invoice->customField('GST Tax Through');
            
            // Determine who pays: CONSIGNEE or CONSIGNOR (default)
            $payingCustomerType = $gstTaxThrough === 'CONSIGNEE' ? 'CONSIGNEE' : 'CONSIGNOR';
            
            // Get the appropriate customer ID based on who pays
            $payingCustomerId = null;
            if ($payingCustomerType === 'CONSIGNEE' && $invoice->consignee_customer_id) {
                $payingCustomerId = $invoice->consignee_customer_id;
            } else {
                $payingCustomerId = $invoice->customer_id;
            }

            // Skip if no paying customer
            if (!$payingCustomerId) {
                continue;
            }

            // Initialize customer sales entry if not exists
            if (!isset($customerSales[$payingCustomerId])) {
                $customerSales[$payingCustomerId] = (object) [
                    'id' => $payingCustomerId,
                    'name' => '',
                    'totalAmount' => 0,
                    'invoiceCount' => 0,
                    'invoices' => collect(),
                ];
            }

            // Add invoice to customer's invoices
            $customerSales[$payingCustomerId]->invoices->push($invoice);

            // Add invoice amount to customer total
            $customerSales[$payingCustomerId]->totalAmount += $invoice->base_total;
            $customerSales[$payingCustomerId]->invoiceCount++;
            $totalAmount += $invoice->base_total;
        }

        // Enrich customer data with names
        $customerIds = array_keys($customerSales);
        if (!empty($customerIds)) {
            $customersData = Customer::whereIn('id', $customerIds)->get();
            foreach ($customersData as $customer) {
                if (isset($customerSales[$customer->id])) {
                    $customerSales[$customer->id]->name = $customer->name ?? $customer->display_name;
                }
            }
        }

        // Convert to array for the view
        $customers = collect($customerSales)->values();

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id);
        $from_date = Carbon::createFromFormat('Y-m-d', $request->from_date)->translatedFormat($dateFormat);
        $to_date = Carbon::createFromFormat('Y-m-d', $request->to_date)->translatedFormat($dateFormat);
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', $company->id));

        $colors = [
            'primary_text_color',
            'heading_text_color',
            'section_heading_text_color',
            'border_color',
            'body_text_color',
            'footer_text_color',
            'footer_total_color',
            'footer_bg_color',
            'date_text_color',
        ];

        $colorSettings = CompanySetting::whereIn('option', $colors)
            ->whereCompany($company->id)
            ->get();

        view()->share([
            'customers' => $customers,
            'totalAmount' => $totalAmount,
            'colorSettings' => $colorSettings,
            'company' => $company,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'currency' => $currency,
        ]);

        $pdf = PDF::loadView('app.pdf.reports.sales-customers');

        if ($request->has('preview')) {
            return view('app.pdf.reports.sales-customers');
        }

        if ($request->has('download')) {
            return $pdf->download();
        }

        return $pdf->stream();
    }
}
