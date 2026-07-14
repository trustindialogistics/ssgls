<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LrReceiptLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $docketNumber = trim((string) $request->query('docket_number', ''));

        if ($docketNumber === '') {
            return response()->json(['found' => false]);
        }

        $invoiceNumbers = collect([
            $docketNumber,
            preg_replace('/^DOC/i', 'INV', $docketNumber),
            preg_replace('/^INV/i', 'DOC', $docketNumber),
        ])->filter()->unique()->values()->all();

        $lrReceipt = Invoice::whereCompany()
            ->where('template_name', Invoice::TEMPLATE_LR_RECEIPT)
            ->where(function ($query) use ($invoiceNumbers) {
                $query->whereIn('invoice_number', $invoiceNumbers);
            })
            ->with(['fields.customField', 'items.fields.customField'])
            ->latest('id')
            ->first();

        if (! $lrReceipt) {
            return response()->json(['found' => false]);
        }

        $item = $lrReceipt->items->first();
        $gstTaxThrough = strtoupper((string) $this->customFieldValue($lrReceipt->fields, 'GST Tax Payable By'));
        $partyNameAddress = match ($gstTaxThrough) {
            'CONSIGNOR' => $this->customFieldValue($lrReceipt->fields, 'Consignor'),
            'CONSIGNEE' => $this->customFieldValue($lrReceipt->fields, 'Consignee'),
            default => null,
        };
        $partyCustomer = $this->findCustomerByPartyDetails($partyNameAddress);

        return response()->json([
            'found' => true,
            'docket_number' => preg_replace('/^INV/i', 'DOC', $lrReceipt->invoice_number),
            'invoice_date' => $lrReceipt->invoice_date ? Carbon::parse($lrReceipt->invoice_date)->format('Y-m-d') : null,
            'customer_id' => $partyCustomer?->id,
            'invoice_fields' => [
                'GST Tax Through' => $gstTaxThrough,
            ],
            'item_fields' => [
                'From' => $this->customFieldValue($lrReceipt->fields, 'From'),
                'Destination' => $this->customFieldValue($lrReceipt->fields, 'To'),
                'Vehicle No' => $this->customFieldValue($lrReceipt->fields, 'Truck No'),
                'Invoice No' => $item ? $this->customFieldValue($item->fields, 'Invoice No') : null,
                'Pkg' => $item ? $this->customFieldValue($item->fields, 'No of Articles') : null,
                'Charged Weight Kgs' => $item ? $this->customFieldValue($item->fields, 'Charged Weight') : null,
                'Rate' => $this->customFieldValue($lrReceipt->fields, 'Basic Freight'),
                'Other Charge' => (
                    (float) $this->customFieldValue($lrReceipt->fields, 'Hamali') +
                    (float) $this->customFieldValue($lrReceipt->fields, 'FOV') +
                    (float) $this->customFieldValue($lrReceipt->fields, 'Local Collection') +
                    (float) $this->customFieldValue($lrReceipt->fields, 'Other Charge')
                ),
                'LR Charge' => $this->customFieldValue($lrReceipt->fields, 'Docket Charge'),
                'DD Charge' => $this->customFieldValue($lrReceipt->fields, 'Door Delivery'),
            ],
        ]);
    }

    private function customFieldValue($fields, string $label): mixed
    {
        $normalizedLabel = $this->normalizeLabel($label);

        $field = $fields->first(function ($field) use ($normalizedLabel) {
            $customField = $field->customField;

            return $customField && (
                $this->normalizeLabel($customField->label) === $normalizedLabel ||
                $this->normalizeLabel($customField->name) === $normalizedLabel
            );
        });

        return $field?->defaultAnswer;
    }

    private function findCustomerByPartyDetails(?string $partyDetails): ?Customer
    {
        $partyName = collect(preg_split('/\r\n|\r|\n/', (string) $partyDetails))
            ->map(fn ($line) => trim($line))
            ->first(fn ($line) => $line !== '');

        if (! $partyName) {
            return null;
        }

        // Try exact match first (fast, uses index)
        $customer = Customer::whereCompany()
            ->where('name', $partyName)
            ->first();

        if ($customer) {
            return $customer;
        }

        // Try case-insensitive match on name
        $customer = Customer::whereCompany()
            ->whereRaw('LOWER(name) = ?', [strtolower($partyName)])
            ->first();

        if ($customer) {
            return $customer;
        }

        // Try company_name
        $customer = Customer::whereCompany()
            ->whereRaw('LOWER(company_name) = ?', [strtolower($partyName)])
            ->first();

        return $customer;
    }

    private function normalizeLabel(?string $label): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
    }
}
