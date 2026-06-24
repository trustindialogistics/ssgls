<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $limit = $request->input('limit', 10);
        $with = ['customer', 'media', 'currency', 'payments'];

        if (in_array($request->input('template_name'), [Invoice::TEMPLATE_LR_RECEIPT, Invoice::TEMPLATE_LORRY_RECEIPT], true)) {
            $with[] = 'fields.customField';
        }

        $invoices = Invoice::whereCompany()
            ->applyFilters($request->all())
            ->with($with)
            ->latest()
            ->paginateData($limit);

        return InvoiceResource::collection($invoices)
            ->additional(['meta' => [
                'invoice_total_count' => Invoice::whereCompany()
                    ->when($request->filled('template_name'), function ($query) use ($request) {
                        $query->where('template_name', $request->input('template_name'));
                    })
                    ->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return InvoiceResource
     */
    public function store(Requests\InvoicesRequest $request): InvoiceResource
    {
        $this->authorize('create', Invoice::class);

        $invoice = Invoice::createInvoice($request);

        if ($request->has('invoiceSend')) {
            $invoice->send($request->subject, $request->body);
        }

        GenerateInvoicePdfJob::dispatch($invoice);

        return new InvoiceResource($invoice->load(['media', 'customer.billingAddress', 'customer.shippingAddress', 'currency', 'items.taxes', 'items.fields.customField', 'fields.customField', 'taxes']));
    }

    /**
     * Display the specified resource.
     *
     * @return InvoiceResource
     */
    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        if ($request->filled('template_name') && $invoice->template_name !== $request->input('template_name')) {
            abort(404);
        }

        return new InvoiceResource($invoice->load(['media', 'customer.billingAddress', 'customer.shippingAddress', 'currency', 'items.taxes', 'items.fields.customField', 'fields.customField', 'taxes']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return InvoiceResource|JsonResponse
     */
    public function update(Requests\InvoicesRequest $request, Invoice $invoice): InvoiceResource|JsonResponse
    {
        $this->authorize('update', $invoice);

        $invoice = $invoice->updateInvoice($request);

        if (is_string($invoice)) {
            return respondJson($invoice, $invoice);
        }

        GenerateInvoicePdfJob::dispatch($invoice, true);

        return new InvoiceResource($invoice->load(['media', 'customer.billingAddress', 'customer.shippingAddress', 'currency', 'items.taxes', 'items.fields.customField', 'fields.customField', 'taxes']));
    }

    /**
     * delete the specified resources in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function delete(DeleteInvoiceRequest $request): JsonResponse
    {
        $this->authorize('delete multiple invoices');

        $ids = Invoice::whereCompany()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        Invoice::deleteInvoices($ids);

        return response()->json([
            'success' => true,
        ]);
    }
}
