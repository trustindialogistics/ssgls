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
        $disposition = $request->has('download') ? 'attachment' : 'inline';

        if ($request->has('preview')) {
            return $invoice->getPDFData(null, $includeDocuments);
        }

        if ($includeDocuments) {
            $pdf = $invoice->getPDFData(null, true);

            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$invoice->invoice_number.'-with-documents.pdf"',
            ]);
        }

        if ($request->has('copy')) {
            $pdf = $invoice->getPDFData($request->query('copy'));

            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$invoice->invoice_number.'-'.$request->query('copy').'.pdf"',
            ]);
        }

        $response = $invoice->getGeneratedPDFOrStream('invoice');
        if ($request->has('download')) {
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$invoice->invoice_number.'.pdf"');
        }

        return $response;
    }
}
