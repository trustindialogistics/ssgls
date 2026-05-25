<?php

namespace App\Http\Controllers\V1\Customer\General;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Response
     */
    public function __invoke(Request $request)
    {
        $user = Auth::guard('customer')->user();

        $amountDue = Invoice::whereCustomer($user->id)
            ->whereRegularInvoice()
            ->where('status', '<>', 'DRAFT')
            ->sum('due_amount');
        $invoiceCount = Invoice::whereCustomer($user->id)
            ->whereRegularInvoice()
            ->where('status', '<>', 'DRAFT')
            ->count();
        $estimatesCount = Estimate::whereCustomer($user->id)
            ->where('status', '<>', 'DRAFT')
            ->count();
        $paymentCount = Payment::whereCustomer($user->id)
            ->where(function ($query) {
                $query->whereNull('invoice_id')
                    ->orWhereHas('invoice', function ($query) {
                        $query->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE);
                    });
            })
            ->count();

        return response()->json([
            'due_amount' => $amountDue,
            'recentInvoices' => Invoice::whereCustomer($user->id)->whereRegularInvoice()->where('status', '<>', 'DRAFT')->take(5)->latest()->get(),
            'recentEstimates' => Estimate::whereCustomer($user->id)->where('status', '<>', 'DRAFT')->take(5)->latest()->get(),
            'invoice_count' => $invoiceCount,
            'estimate_count' => $estimatesCount,
            'payment_count' => $paymentCount,
        ]);
    }
}
