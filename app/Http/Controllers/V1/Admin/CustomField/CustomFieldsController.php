<?php

namespace App\Http\Controllers\V1\Admin\CustomField;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomFieldRequest;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomFieldsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', CustomField::class);

        if ($this->isTransportInvoiceTemplate($request->template_name)) {
            $this->ensureTransportInvoiceFields($request);
            $this->removeEmptyDuplicateTransportInvoiceFields(
                (int) $request->header('company'),
                $this->getTransportInvoiceSlugs($request->template_name)
            );
        }

        $limit = $request->has('limit') ? $request->limit : 5;
        $transportInvoiceSlugs = $this->getTransportInvoiceSlugs();
        $selectedTransportInvoiceSlugs = $this->getTransportInvoiceSlugs($request->template_name);

        $customFields = CustomField::applyFilters($request->all())
            ->whereCompany()
            ->when($request->has('template_name'), function ($query) use ($request, $transportInvoiceSlugs, $selectedTransportInvoiceSlugs) {
                if ($this->isTransportInvoiceTemplate($request->template_name)) {
                    $query->whereIn('slug', $selectedTransportInvoiceSlugs);

                    return;
                }

                $query->whereNotIn('slug', $transportInvoiceSlugs);
            })
            ->latest()
            ->paginateData($limit);

        return CustomFieldResource::collection($customFields);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\CustomFieldRequest  $request
     * @return Response
     */
    public function store(CustomFieldRequest $request)
    {
        $this->authorize('create', CustomField::class);

        $customField = CustomField::createCustomField($request);

        return new CustomFieldResource($customField);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(CustomField $customField)
    {
        $this->authorize('view', $customField);

        return new CustomFieldResource($customField);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(CustomFieldRequest $request, CustomField $customField)
    {
        $this->authorize('update', $customField);

        $customField->updateCustomField($request);

        return new CustomFieldResource($customField);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(CustomField $customField)
    {
        $this->authorize('delete', $customField);

        if ($customField->customFieldValues()->exists()) {
            $customField->customFieldValues()->delete();
        }

        $customField->forceDelete();

        return response()->json([
            'success' => true,
        ]);
    }

    private function ensureTransportInvoiceFields(Request $request): void
    {
        $companyId = $request->header('company');

        if (! $companyId) {
            return;
        }

        $fields = $this->getTransportInvoiceFields($request->template_name);
        $fieldNamesByModel = collect($fields)->map(fn ($definitions) => collect($definitions)->pluck('name')->all());
        $existingFields = CustomField::where('company_id', $companyId)
            ->where(function ($query) use ($fieldNamesByModel) {
                foreach ($fieldNamesByModel as $modelType => $fieldNames) {
                    $query->orWhere(function ($query) use ($modelType, $fieldNames) {
                        $query->where('model_type', $modelType)
                            ->whereIn('name', $fieldNames);
                    });
                }
            })
            ->get()
            ->keyBy(fn ($field) => $field->model_type.'|'.$field->name);

        foreach ($fields as $modelType => $fieldDefinitions) {
            foreach ($fieldDefinitions as $index => $fieldDefinition) {
                $fieldName = $fieldDefinition['name'];
                $fieldType = $fieldDefinition['type'] ?? 'Input';
                $defaultAnswerKey = getCustomFieldValueKey($fieldType);
                $attributes = [
                    'slug' => $this->makeTransportInvoiceSlug($modelType, $fieldName),
                    'label' => $fieldName,
                    'type' => $fieldType,
                    'options' => $fieldDefinition['options'] ?? null,
                    'is_required' => false,
                    'order' => 900 + $index,
                    $defaultAnswerKey => $fieldDefinition['default_answer'] ?? null,
                ];
                $existingField = $existingFields->get($modelType.'|'.$fieldName);

                if (! $existingField) {
                    CustomField::create([
                        'company_id' => $companyId,
                        'model_type' => $modelType,
                        'name' => $fieldName,
                        ...$attributes,
                    ]);

                    continue;
                }

                $existingField->fill($attributes);

                if ($existingField->isDirty()) {
                    $existingField->save();
                }
            }
        }
    }

    private function getTransportInvoiceSlugs(?string $templateName = null): array
    {
        if (! $templateName) {
            return array_values(array_unique(array_merge(
                $this->getTransportInvoiceSlugs('ssgl_transport'),
                $this->getTransportInvoiceSlugs('office_invoice'),
                $this->getTransportInvoiceSlugs('lr_receipt')
            )));
        }

        $fieldNames = $this->getTransportInvoiceFields($templateName);

        $slugs = [];

        foreach ($fieldNames as $modelType => $definitions) {
            foreach ($definitions as $definition) {
                $slugs[] = $this->makeTransportInvoiceSlug($modelType, $definition['name']);
            }
        }

        return $slugs;
    }

    private function getTransportInvoiceFields(?string $templateName = null): array
    {
        if ($templateName === 'lr_receipt') {
            return [
                'Invoice' => [
                    ['name' => 'Time', 'type' => 'Time'],
                    ['name' => 'From'],
                    ['name' => 'To'],
                    ['name' => 'Truck No'],
                    ['name' => 'Consignor'],
                    ['name' => 'Consignor Phone No'],
                    ['name' => 'Consignor GST No'],
                    ['name' => 'Consignee'],
                    ['name' => 'Consignee Phone No'],
                    ['name' => 'Consignee GST No'],
                    ['name' => 'Mode of Payment', 'type' => 'Dropdown', 'options' => [
                        ['name' => 'TO PAY'],
                        ['name' => 'PAID'],
                        ['name' => 'TO BE BILLED AT'],
                    ]],
                    ['name' => 'GST Tax Payable By', 'type' => 'Dropdown', 'options' => [
                        ['name' => 'Consignor'],
                        ['name' => 'Consignee'],
                    ]],
                ],
                'Item' => [
                    ['name' => 'Description of Goods'],
                    ['name' => 'HSN Code'],
                    ['name' => 'Delivery At'],
                    ['name' => 'E-way Bill No'],
                    ['name' => 'No of Articles', 'type' => 'Number'],
                    ['name' => 'Packing'],
                    ['name' => 'Actual Weight'],
                    ['name' => 'Charged Weight'],
                    ['name' => 'Invoice No'],
                    ['name' => 'Goods Value'],
                    ['name' => 'POD Required'],
                    ['name' => 'Basic Freight', 'type' => 'Number'],
                    ['name' => 'Local Collection', 'type' => 'Number'],
                    ['name' => 'Door Delivery', 'type' => 'Number'],
                    ['name' => 'Hamali', 'type' => 'Number'],
                    ['name' => 'Docket Charge', 'type' => 'Number', 'default_answer' => 100],
                    ['name' => 'Other Charge', 'type' => 'Number'],
                    ['name' => 'FOV', 'type' => 'Number'],
                ],
            ];
        }

        if ($templateName === 'office_invoice') {
            return [
                'Invoice' => [
                    [
                        'name' => 'GST Tax Through',
                        'type' => 'Dropdown',
                        'options' => [
                            ['name' => 'CONSIGNOR'],
                            ['name' => 'CONSIGNEE'],
                        ],
                    ],
                    [
                        'name' => 'Signature Name',
                        'type' => 'Dropdown',
                        'options' => [
                            ['name' => 'Madanlal Sharma'],
                            ['name' => 'Satpal Sharma'],
                        ],
                    ],
                ],
                'Item' => [
                    ['name' => 'Consignment Number'],
                    ['name' => 'Consignment Date', 'type' => 'Date'],
                    ['name' => 'Invoice No'],
                    ['name' => 'From'],
                    ['name' => 'Destination'],
                    ['name' => 'Vehicle No'],
                    ['name' => 'Pkg'],
                    ['name' => 'Charged Weight Kgs'],
                    ['name' => 'Rate', 'type' => 'Number'],
                    ['name' => 'Other Charge', 'type' => 'Number'],
                    ['name' => 'LR Charge', 'type' => 'Number'],
                    ['name' => 'DD Charge', 'type' => 'Number'],
                ],
            ];
        }

        return [
            'Invoice' => [
                ['name' => 'Billing Branch Name Address'],
                ['name' => 'PAN No'],
                ['name' => 'GSTIN'],
                ['name' => 'Party Code'],
                ['name' => 'Branch Code'],
                ['name' => 'Tick Bill Type'],
                ['name' => 'Basis Of Charges'],
                ['name' => 'Cash'],
                ['name' => 'Cheque No'],
                ['name' => 'Payment Date', 'type' => 'Date'],
                ['name' => 'Bank'],
                ['name' => 'Others'],
                ['name' => 'Enclosures'],
                ['name' => 'Service Tax Through'],
                ['name' => 'EMP Code'],
                ['name' => 'Prepared By'],
                ['name' => 'Checked By'],
                [
                    'name' => 'Signature Name',
                    'type' => 'Dropdown',
                    'options' => [
                        ['name' => 'Madanlal Sharma'],
                        ['name' => 'Satpal Sharma'],
                    ],
                ],
                ['name' => 'Rupees In Words'],
            ],
            'Item' => [
                ['name' => 'Consignment Number'],
                ['name' => 'Consignment Date', 'type' => 'Date'],
                ['name' => 'Invoice No'],
                ['name' => 'Destination'],
                ['name' => 'Vehicle No'],
                ['name' => 'Pkg'],
                ['name' => 'Charged Weight Kgs'],
                ['name' => 'Rate', 'type' => 'Number'],
                ['name' => 'Other Charge', 'type' => 'Number'],
                ['name' => 'DD Charge', 'type' => 'Number'],
            ],
        ];
    }

    private function makeTransportInvoiceSlug(string $modelType, string $name): string
    {
        return 'CUSTOM_'.$modelType.'_'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($name, '_'));
    }

    private function removeEmptyDuplicateTransportInvoiceFields(int $companyId, array $slugs): void
    {
        if (! $companyId || $slugs === []) {
            return;
        }

        CustomField::where('company_id', $companyId)
            ->whereIn('slug', $slugs)
            ->withCount('customFieldValues')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($field) => $field->model_type.'|'.$field->slug)
            ->each(function ($fields) {
                if ($fields->count() < 2) {
                    return;
                }

                $keep = $fields
                    ->sortBy([
                        ['custom_field_values_count', 'desc'],
                        ['id', 'asc'],
                    ])
                    ->first();

                $fields
                    ->reject(fn ($field) => $field->id === $keep->id)
                    ->filter(fn ($field) => $field->custom_field_values_count === 0)
                    ->each
                    ->delete();
            });
    }

    private function isTransportInvoiceTemplate(?string $templateName): bool
    {
        return in_array($templateName, ['ssgl_transport', 'office_invoice', 'lr_receipt'], true);
    }
}
