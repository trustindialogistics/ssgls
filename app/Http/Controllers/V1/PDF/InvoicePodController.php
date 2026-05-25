<?php

namespace App\Http\Controllers\V1\PDF;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

class InvoicePodController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $media = $invoice->getFirstMedia('pod');

        if ($media) {
            return response()->file($media->getPath());
        }

        return response()->json([
            'error' => 'pod_not_found',
        ], 404);
    }
}
