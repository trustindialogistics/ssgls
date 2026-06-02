<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Space\PdfTemplateUtils;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceTemplatesController extends Controller
{
    /**
     * Handle the incoming request.
     *
     *
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $allowedTemplates = ['office_invoice', 'lr_receipt', 'lorry_receipt'];

        $invoiceTemplates = array_values(array_filter(
            PdfTemplateUtils::getFormattedTemplates('invoice'),
            fn ($template) => in_array($template['name'], $allowedTemplates, true)
        ));

        return response()->json([
            'invoiceTemplates' => $invoiceTemplates,
        ]);
    }
}
