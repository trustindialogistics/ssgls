<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadInvoicePodRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;

class UploadInvoicePodController extends Controller
{
    public function __invoke(UploadInvoicePodRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = json_decode($request->pod);

        $invoice->clearMediaCollection('pod');

        $invoice->addMediaFromBase64($data->data)
            ->usingFileName($data->name)
            ->toMediaCollection('pod');

        return new InvoiceResource($invoice->fresh()->load('media'));
    }
}
