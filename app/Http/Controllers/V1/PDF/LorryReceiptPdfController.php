<?php

namespace App\Http\Controllers\V1\PDF;

use App\Http\Controllers\Controller;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;

class LorryReceiptPdfController extends Controller
{
    public function __invoke(Request $request, LorryReceipt $lorryReceipt)
    {
        if ($request->has('preview')) {
            return $lorryReceipt->getPDFData();
        }

        $pdf = $lorryReceipt->getPDFData();

        return response()->make($pdf->stream(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="lorry-receipt-'.$lorryReceipt->unique_hash.'.pdf"',
        ]);
    }
}
