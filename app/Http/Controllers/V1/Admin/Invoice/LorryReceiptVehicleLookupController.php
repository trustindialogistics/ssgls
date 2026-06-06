<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LorryReceiptVehicleLookupController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const FIELD_LABELS = [
        'Regd at',
        'Body Type',
        'Make',
        'Model',
        'Colour',
        'Chasis No',
        'Engine No',
        'Owner Name',
        'Owner Address',
        'Owner Phone No',
        'Financer Name',
        'Financer Address',
        'Paid To',
        'Final Paid To',
    ];

    /**
     * @var array<string, string>
     */
    private const STANDALONE_FIELD_MAP = [
        'Regd at' => 'regd_at',
        'Body Type' => 'body_type',
        'Make' => 'make',
        'Model' => 'vehicle_model',
        'Colour' => 'colour',
        'Chasis No' => 'chasis_no',
        'Engine No' => 'engine_no',
        'Owner Name' => 'owner_name',
        'Owner Address' => 'owner_address',
        'Owner Phone No' => 'owner_phone',
        'Financer Name' => 'financer_name',
        'Financer Address' => 'financer_address',
        'Paid To' => 'paid_to',
        'Final Paid To' => 'final_paid_to',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $lorryNo = trim((string) $request->query('lorry_no', ''));

        if ($lorryNo === '') {
            return response()->json(['found' => false]);
        }

        $normalizedLorryNo = $this->normalizeVehicleNumber($lorryNo);

        $receipt = Invoice::whereCompany()
            ->where('template_name', Invoice::TEMPLATE_LORRY_RECEIPT)
            ->whereHas('fields.customField', function ($query) {
                $query->where('label', 'Lorry No')
                    ->orWhere('name', 'Lorry No');
            })
            ->with(['fields.customField'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->first(function (Invoice $invoice) use ($normalizedLorryNo) {
                return $this->normalizeVehicleNumber(
                    $this->customFieldValue($invoice->fields, 'Lorry No')
                ) === $normalizedLorryNo;
            });

        if ($receipt) {
            return response()->json([
                'found' => true,
                'invoice_id' => $receipt->id,
                'fields' => $this->invoiceFields($receipt),
            ]);
        }

        $standaloneReceipt = $this->findStandaloneReceipt($request, $normalizedLorryNo);

        if (! $standaloneReceipt) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'lorry_receipt_id' => $standaloneReceipt->id,
            'fields' => $this->standaloneReceiptFields($standaloneReceipt),
        ]);
    }

    private function invoiceFields(Invoice $receipt): array
    {
        $fields = collect(self::FIELD_LABELS)
            ->mapWithKeys(fn (string $label): array => [
                $label => $this->customFieldValue($receipt->fields, $label),
            ])
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->all();

        if (! isset($fields['Paid To']) && isset($fields['Owner Name'])) {
            $fields['Paid To'] = $fields['Owner Name'];
        }

        if (! isset($fields['Final Paid To']) && isset($fields['Owner Name'])) {
            $fields['Final Paid To'] = $fields['Owner Name'];
        }

        return $fields;
    }

    private function findStandaloneReceipt(Request $request, string $normalizedLorryNo): ?LorryReceipt
    {
        return LorryReceipt::query()
            ->where('company_id', $request->header('company'))
            ->whereNotNull('lorry_no')
            ->latest('id')
            ->limit(100)
            ->get()
            ->first(function (LorryReceipt $receipt) use ($normalizedLorryNo) {
                return $this->normalizeVehicleNumber($receipt->lorry_no) === $normalizedLorryNo;
            });
    }

    private function standaloneReceiptFields(LorryReceipt $receipt): array
    {
        $fields = collect(self::STANDALONE_FIELD_MAP)
            ->mapWithKeys(fn (string $attribute, string $label): array => [
                $label => $receipt->{$attribute},
            ])
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->all();

        if (! isset($fields['Paid To']) && isset($fields['Owner Name'])) {
            $fields['Paid To'] = $fields['Owner Name'];
        }

        if (! isset($fields['Final Paid To']) && isset($fields['Owner Name'])) {
            $fields['Final Paid To'] = $fields['Owner Name'];
        }

        return $fields;
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

    private function normalizeLabel(?string $label): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
    }

    private function normalizeVehicleNumber(?string $value): string
    {
        return $this->normalizeLabel($value);
    }
}
