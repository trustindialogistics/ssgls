<?php

namespace App\Http\Controllers\V1\PDF;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Response
     */
    public function __invoke(Request $request, Invoice $invoice)
    {
        if ($request->filled('template_name') && $invoice->template_name !== $request->input('template_name')) {
            abort(404);
        }

        $includeDocuments = $request->has('include_documents') || $request->has('documents');

        if ($request->has('preview')) {
            return $invoice->getPDFData(null, $includeDocuments);
        }

        if ($includeDocuments) {
            $pdf = $invoice->getPDFData(null, true);

            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'-with-documents.pdf"',
            ]);
        }

        if ($request->has('copy')) {
            $pdf = $invoice->getPDFData($request->query('copy'));

            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'-'.$request->query('copy').'.pdf"',
            ]);
        }

        return $invoice->getGeneratedPDFOrStream('invoice');
    }
}
