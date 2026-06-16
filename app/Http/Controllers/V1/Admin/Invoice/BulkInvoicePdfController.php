<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class BulkInvoicePdfController extends Controller
{
    /**
     * Download multiple invoices as a single merged PDF.
     * Accepts: ids[] array of invoice IDs
     * Optional: copy_type for LR Receipts (consignee, driver, consignor, ho, file)
     * Optional: multi_copy=true to include all 5 copy types per LR Receipt
     */
    public function __invoke(Request $request)
    {
        $this->authorize('view', Invoice::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:invoices,id',
            'copy_type' => 'nullable|string|in:consignee,driver,consignor,ho,file',
            'multi_copy' => 'nullable|boolean',
        ]);

        $invoices = Invoice::with(['customer', 'items', 'items.fields', 'items.fields.customField', 'taxes', 'fields', 'fields.customField'])
            ->whereCompany()
            ->whereIn('id', $request->ids)
            ->orderBy('sequence_number', 'desc')
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['error' => 'No invoices found'], 404);
        }

        $pdfPages = [];
        $copyTypes = ['consignee', 'driver', 'consignor', 'ho', 'file'];

        foreach ($invoices as $invoice) {
            if ($request->boolean('multi_copy') && $invoice->template_name === Invoice::TEMPLATE_LR_RECEIPT) {
                foreach ($copyTypes as $copyType) {
                    $pdf = $invoice->getPDFData($copyType);
                    $pdfPages[] = $pdf;
                }
            } elseif ($request->filled('copy_type') && $invoice->template_name === Invoice::TEMPLATE_LR_RECEIPT) {
                $pdf = $invoice->getPDFData($request->copy_type);
                $pdfPages[] = $pdf;
            } else {
                $pdf = $invoice->getPDFData(null);
                $pdfPages[] = $pdf;
            }
        }

        $mergedPdf = $this->mergePdfs($pdfPages);

        $filename = $this->getFilename($invoices, $request);

        return response()->make($mergedPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function mergePdfs(array $pdfs): string
    {
        if (count($pdfs) === 1) {
            return $pdfs[0]->stream();
        }

        $merger = new \iio\libmergepdf\Merger;
        foreach ($pdfs as $pdf) {
            $merger->addRaw($pdf->stream());
        }

        return $merger->merge();
    }

    private function getFilename($invoices, $request): string
    {
        $templateName = $invoices->first()->template_name;

        return match ($templateName) {
            Invoice::TEMPLATE_LR_RECEIPT => $request->boolean('multi_copy')
                ? 'lr-receipts-multi-copy.pdf'
                : 'lr-receipts.pdf',
            Invoice::TEMPLATE_LORRY_RECEIPT => 'lorry-receipts.pdf',
            default => 'invoices.pdf',
        };
    }
}
