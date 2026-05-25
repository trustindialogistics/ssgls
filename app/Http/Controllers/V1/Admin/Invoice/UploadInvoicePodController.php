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

        $invoice->addMediaFromBase64($this->sanitizeBase64Pdf($data->data, $data->name))
            ->usingFileName($data->name)
            ->toMediaCollection('pod');

        return new InvoiceResource($invoice->fresh()->load('media'));
    }

    private function sanitizeBase64Pdf(string $data, string $fileName): string
    {
        if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'pdf') {
            return $data;
        }

        $parts = explode(',', $data, 2);

        if (count($parts) !== 2) {
            return $data;
        }

        $decoded = base64_decode($parts[1], true);

        if ($decoded === false) {
            return $data;
        }

        $pdfOffset = strpos($decoded, '%PDF');

        if ($pdfOffset === false || $pdfOffset === 0) {
            return $data;
        }

        return $parts[0].','.base64_encode(substr($decoded, $pdfOffset));
    }
}
