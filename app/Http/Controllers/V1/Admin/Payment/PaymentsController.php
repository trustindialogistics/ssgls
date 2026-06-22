<?php

namespace App\Http\Controllers\V1\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeletePaymentsRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $payments = Payment::whereCompany()
            ->with(['customer', 'invoice.customer', 'paymentMethod', 'fields.customField', 'creator', 'updatedBy'])
            ->join('customers', 'customers.id', '=', 'payments.customer_id')
            ->leftJoin('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where(function ($query) {
                $query->whereNull('payments.invoice_id')
                    ->orWhere('invoices.template_name', Invoice::TEMPLATE_OFFICE_INVOICE);
            })
            ->applyFilters($request->all())
            ->select('payments.*', 'customers.name', 'invoices.invoice_number', 'payment_methods.name as payment_mode')
            ->latest()
            ->paginateData($limit);

        return PaymentResource::collection($payments)
            ->additional(['meta' => [
                'payment_total_count' => Payment::whereCompany()
                    ->where(function ($query) {
                        $query->whereNull('invoice_id')
                            ->orWhereHas('invoice', function ($query) {
                                $query->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE);
                            });
                    })
                    ->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(PaymentRequest $request)
    {
        $this->authorize('create', Payment::class);

        $payment = Payment::createPayment($request);

        return new PaymentResource($payment->load(['customer', 'invoice.customer', 'paymentMethod', 'fields.customField']));
    }

    public function show(Request $request, Payment $payment)
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment->load(['customer', 'invoice.customer', 'paymentMethod', 'fields.customField']));
    }

    public function update(PaymentRequest $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $payment = $payment->updatePayment($request);

        return new PaymentResource($payment->load(['customer', 'invoice.customer', 'paymentMethod', 'fields.customField']));
    }

    public function delete(DeletePaymentsRequest $request)
    {
        $this->authorize('delete multiple payments');

        $ids = Payment::whereCompany()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        Payment::deletePayments($ids);

        return response()->json([
            'success' => true,
        ]);
    }
}
