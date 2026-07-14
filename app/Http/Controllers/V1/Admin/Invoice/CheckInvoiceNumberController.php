<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInvoiceNumberController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoiceNumber = trim((string) $request->query('invoice_number', ''));
        $templateName = trim((string) $request->query('template_name', ''));

        if ($invoiceNumber === '') {
            return response()->json(['exists' => false]);
        }

        $exists = Invoice::whereCompany()
            ->where('invoice_number', $invoiceNumber)
            ->where('template_name', $templateName)
            ->exists();

        return response()->json(['exists' => $exists]);
    }
}
