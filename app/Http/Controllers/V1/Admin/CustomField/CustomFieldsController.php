<?php

namespace App\Http\Controllers\V1\Admin\CustomField;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomFieldRequest;
use App\Http\Resources\CustomFieldResource;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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
            ->where(function ($query) use ($request, $transportInvoiceSlugs, $selectedTransportInvoiceSlugs) {
                if ($request->has('template_name') && $this->isTransportInvoiceTemplate($request->template_name)) {
                    $query->whereIn('slug', $selectedTransportInvoiceSlugs);
                } else {
                    $query->whereNotIn('slug', $transportInvoiceSlugs);
                }
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
                $defaultAnswer = $request->template_name === 'lorry_receipt'
                    ? null
                    : ($fieldDefinition['default_answer'] ?? null);
                $attributes = [
                    'slug' => $this->makeTransportInvoiceSlug($modelType, $fieldName),
                    'label' => $fieldName,
                    'type' => $fieldType,
                    'options' => $fieldDefinition['options'] ?? null,
                    'is_required' => false,
                    'order' => 900 + $index,
                    $defaultAnswerKey => $defaultAnswer,
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
                $this->getTransportInvoiceSlugs('office_invoice'),
                $this->getTransportInvoiceSlugs('lr_receipt'),
                $this->getTransportInvoiceSlugs('lorry_receipt')
            )));
        }

        $fieldNames = $this->getTransportInvoiceFields($templateName);

        if ($templateName === 'lorry_receipt') {
            $fieldNames['Invoice'] = array_merge(
                $fieldNames['Invoice'],
                collect($this->getLegacyLorryReceiptFieldNames())
                    ->map(fn (string $name): array => ['name' => $name])
                    ->all()
            );
        }

        $slugs = [];

        foreach ($fieldNames as $modelType => $definitions) {
            foreach ($definitions as $definition) {
                $slugs[] = $this->makeTransportInvoiceSlug($modelType, $definition['name']);
            }
        }

        return $slugs;
    }

    /**
     * @return array<int, string>
     */
    private function getLegacyLorryReceiptFieldNames(): array
    {
        return [
            'Lorry Hire Amount',
            'Other Charges Amount',
            'Gross Hire Rupees',
            'Advance Cash Cheque No',
            'Advance Bank',
            'Advance Amount',
            'Balance Payable At',
            'Balance Amount',
            'Balance Rupees Only',
            'Detention Amount',
            'Extra Hire Amount',
            'Final Other Amount',
            'Final Total Extra Amount',
            'Grand Total',
            'Less Advance Other Branch Amount',
            'Less Deduction Claims Amount',
            'Total Less Amount',
            'Final Balance Code',
            'Net Amount Payable',
            'Final Cash Cheque No',
            'Final Rupees Only',
        ];
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
                    ['name' => 'No of Articles', 'type' => 'Input'],
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

        if ($templateName === 'lorry_receipt') {
            return [
                'Invoice' => [
                    ['name' => 'From', 'default_answer' => 'Vapi'],
                    ['name' => 'To', 'default_answer' => 'Bengaluru'],
                    ['name' => 'No Of Pages', 'default_answer' => 1],
                    ['name' => 'No Of Packages', 'default_answer' => 1000],
                    ['name' => 'Actual Weight', 'default_answer' => '100 kg'],
                    ['name' => 'Charge Weight', 'default_answer' => '1000 kg'],
                    ['name' => 'Lorry No', 'default_answer' => 'GJ05BC1234'],
                    ['name' => 'Regd at', 'default_answer' => 'Honda'],
                    ['name' => 'Body Type', 'default_answer' => 'Metal'],
                    ['name' => 'Make', 'default_answer' => 'Black'],
                    ['name' => 'Model', 'default_answer' => '2026'],
                    ['name' => 'Colour', 'default_answer' => 'Black'],
                    ['name' => 'Chasis No', 'default_answer' => '45121564551'],
                    ['name' => 'Engine No', 'default_answer' => '45444CDD'],
                    ['name' => 'Owner Name', 'default_answer' => 'Helloabv'],
                    ['name' => 'Owner Address', 'type' => 'TextArea', 'default_answer' => 'Abc def gh abc def ghi jkl'],
                    ['name' => 'Owner Phone No', 'default_answer' => '123456789'],
                    ['name' => 'Owner Bank Account No'],
                    ['name' => 'Owner PAN No', 'default_answer' => 'MSMSM'],
                    ['name' => 'Financer Address', 'type' => 'TextArea', 'default_answer' => 'ABC DEF GHI JKL'],
                    ['name' => 'Driver Name', 'default_answer' => 'Ramesh Driver'],
                    ['name' => 'Driver Address', 'type' => 'TextArea', 'default_answer' => 'Driver line one driver line two'],
                    ['name' => 'Driver Place', 'default_answer' => 'Vapi'],
                    ['name' => 'Driver Licence No', 'default_answer' => 'DL-2026-1937'],
                    ['name' => 'Driver Licence Date', 'type' => 'Date', 'default_answer' => '2026-05-17'],
                    ['name' => 'Driver Licence Issued By', 'default_answer' => 'RTO Vapi'],
                    ['name' => 'Driver RTO', 'type' => 'TextArea', 'default_answer' => 'Vapi RTO'],
                    ['name' => 'Driver Valid Up To', 'type' => 'Date', 'default_answer' => '2027-05-17'],
                    ['name' => 'Driver Bank Account No'],
                    ['name' => 'Broker Name', 'default_answer' => 'Sample Broker'],
                    ['name' => 'Broker Address', 'type' => 'TextArea', 'default_answer' => 'Broker address line one line two'],
                    ['name' => 'Broker Pan No', 'default_answer' => 'ADV-121'],
                    ['name' => 'Advice Date', 'type' => 'Date', 'default_answer' => '2026-05-17'],
                    ['name' => 'Destination Broker Name', 'default_answer' => 'Bengaluru Broker'],
                    ['name' => 'Destination Broker Address', 'type' => 'TextArea', 'default_answer' => 'Destination broker address sample'],
                    ['name' => 'Broker Phone No', 'default_answer' => '9876543210'],
                    ['name' => 'Broker Bank Account No'],
                    ['name' => 'Paid To', 'default_answer' => 'M K Infrastructure'],
                    ['name' => 'Lorry Hire', 'type' => 'Number', 'default_answer' => 1000],
                    ['name' => 'Add Other Charges', 'type' => 'Number', 'default_answer' => 100],
                    ['name' => 'Advance Paid by Cash/Cheque No', 'type' => 'Dropdown', 'options' => [
                        ['name' => 'UPI'],
                        ['name' => 'CHEQUE'],
                        ['name' => 'CASH'],
                        ['name' => 'NET BANKING'],
                        ['name' => 'NEFT'],
                    ]],
                    ['name' => 'Advance On', 'type' => 'Date', 'default_answer' => '2026-05-17'],
                    ['name' => 'Bank', 'default_answer' => 'ICICI Bank'],
                    ['name' => 'Advance Paid Rs', 'type' => 'Number', 'default_answer' => 1000],
                    ['name' => 'Balance Payable at', 'type' => 'Dropdown', 'options' => [
                        ['name' => 'VAPI'],
                        ['name' => 'UMB'],
                    ]],
                    ['name' => 'Loaded By'],
                    ['name' => 'Final Paid To', 'default_answer' => 'M K Infrastructure'],
                    ['name' => 'Add Detention Rs.', 'type' => 'Number'],
                    ['name' => 'Extra Hire Rs', 'type' => 'Number'],
                    ['name' => 'Other Rs', 'type' => 'Number'],
                    ['name' => 'Less Adv. at other branch', 'type' => 'Number'],
                    ['name' => 'Less Deduction for Claims', 'type' => 'Number'],
                    ['name' => 'Final Balance Amount Paid at', 'type' => 'Dropdown', 'options' => [
                        ['name' => 'VAPI'],
                        ['name' => 'UMB'],
                    ]],
                    ['name' => 'Final Balance Date', 'type' => 'Date', 'default_answer' => '2026-05-17'],
                    ['name' => 'Cash/Cheque No.', 'type' => 'Dropdown', 'options' => [
                        ['name' => 'UPI'],
                        ['name' => 'CHEQUE'],
                        ['name' => 'CASH'],
                        ['name' => 'NET BANKING'],
                        ['name' => 'NEFT'],
                    ]],
                    ['name' => 'Final Bank', 'default_answer' => 'ICICI Bank'],
                    ['name' => 'Received No Of Bilties', 'type' => 'TextArea', 'default_answer' => 'LR NO 1937*471 wt Orthopedic Goods'],
                ],
                'Item' => [],
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

        return [];
    }

    private function makeTransportInvoiceSlug(string $modelType, string $name): string
    {
        return 'CUSTOM_'.$modelType.'_'.Str::upper(Str::slug($name, '_'));
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
        return in_array($templateName, ['office_invoice', 'lr_receipt', 'lorry_receipt'], true);
    }
}
