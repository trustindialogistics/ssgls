<?php

namespace App\Http\Requests;

use App\Models\Address;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('template_name') !== 'lr_receipt') {
            return;
        }

        $customFields = $this->input('customFields', []);

        // Find Consignee details in customFields
        $consigneeNameAddress = $this->getCustomFieldValue($customFields, 'Consignee');
        $consigneePhone = $this->getCustomFieldValue($customFields, 'Consignee Phone No');
        $consigneeGstin = $this->getCustomFieldValue($customFields, 'Consignee GST No');
        $consigneeCity = $this->input('customer.billing.city') ?? $this->input('customer.shipping.city');
        $consigneePrefix = $this->input('customer.prefix');

        $consigneeName = $this->extractNameFromAddressBlock($consigneeNameAddress);

        if (empty($this->input('consignee_customer_id')) && ! empty($consigneeName)) {
            $customer = $this->findOrCreateCustomer(
                $consigneeName,
                $consigneePhone,
                $consigneeGstin,
                $consigneeNameAddress,
                $consigneeCity,
                $consigneePrefix,
                Customer::TYPE_CONSIGNEE
            );

            if ($customer) {
                $this->merge([
                    'consignee_customer_id' => $customer->id,
                ]);
            }
        }

        // Also resolve/create Consignor customer for consistency
        $consignorNameAddress = $this->getCustomFieldValue($customFields, 'Consignor');
        $consignorPhone = $this->getCustomFieldValue($customFields, 'Consignor Phone No');
        $consignorGstin = $this->getCustomFieldValue($customFields, 'Consignor GST No');
        $consignorCity = $this->input('consignor.billing.city') ?? $this->input('consignor.shipping.city');
        $consignorPrefix = $this->input('consignor.prefix');

        $consignorName = $this->extractNameFromAddressBlock($consignorNameAddress);

        if (empty($this->input('customer_id')) && ! empty($consignorName)) {
            $customer = $this->findOrCreateCustomer(
                $consignorName,
                $consignorPhone,
                $consignorGstin,
                $consignorNameAddress,
                $consignorCity,
                $consignorPrefix,
                Customer::TYPE_CUSTOMER
            );

            if ($customer) {
                $this->merge([
                    'customer_id' => $customer->id,
                ]);
            }
        }
    }

    private function getCustomFieldValue(array $customFields, string $label): ?string
    {
        $normalizedLabel = strtolower(preg_replace('/[^a-z0-9]+/i', '', $label));
        foreach ($customFields as $field) {
            $fieldLabel = $field['label'] ?? '';
            if (strtolower(preg_replace('/[^a-z0-9]+/i', '', $fieldLabel)) === $normalizedLabel) {
                return $field['value'] ?? null;
            }
        }

        return null;
    }

    private function extractNameFromAddressBlock(?string $addressBlock): ?string
    {
        if (empty($addressBlock)) {
            return null;
        }
        $lines = preg_split('/\r\n|\r|\n/', trim($addressBlock));

        return ! empty($lines[0]) ? trim($lines[0]) : null;
    }

    private function splitAddress(string $addressBlock, int $maxLineLength = 45): array
    {
        $addressBlock = trim($addressBlock);
        if ($addressBlock === '') {
            return ['', ''];
        }

        $lines = preg_split('/\r\n|\r|\n/', $addressBlock);
        $wrappedLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (mb_strlen($line) > $maxLineLength) {
                $wrapped = wordwrap($line, $maxLineLength, "\n", false);
                $parts = explode("\n", $wrapped);
                foreach ($parts as $part) {
                    $wrappedLines[] = trim($part);
                }
            } else {
                $wrappedLines[] = $line;
            }
        }

        $street1 = isset($wrappedLines[0]) ? $wrappedLines[0] : '';
        $street2 = implode("\n", array_slice($wrappedLines, 1));

        return [$street1, $street2];
    }

    private function findOrCreateCustomer(
        string $name,
        ?string $phone,
        ?string $gstin,
        ?string $addressBlock,
        ?string $city = null,
        ?string $prefix = null,
        string $type = 'CUSTOMER'
    ): ?Customer {
        $companyId = (int) $this->header('company');
        if (! $companyId) {
            return null;
        }

        $gstin = trim((string) $gstin);
        if ($gstin !== '') {
            $customer = Customer::where('company_id', $companyId)
                ->where('type', $type)
                ->where('tax_id', $gstin)
                ->first();
            if ($customer) {
                return $customer;
            }
        }

        $query = Customer::where('company_id', $companyId)
            ->where('type', $type)
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                  ->orWhereRaw('LOWER(name) = ?', [strtolower($name)]);
            });

        if (!empty($city)) {
            $customer = (clone $query)->whereHas('billingAddress', function ($q) use ($city) {
                $q->whereRaw('LOWER(city) = ?', [strtolower(trim($city))]);
            })->first();

            if ($customer) {
                return $customer;
            }
        } else {
            $customer = $query->first();
            if ($customer) {
                return $customer;
            }
        }

        // Parse address block to format billing/shipping address
        $addressLines = preg_split('/\r\n|\r|\n/', trim((string) $addressBlock));
        $actualAddressLines = array_slice($addressLines, 1);
        $actualAddressBlock = implode("\n", $actualAddressLines);

        [$street1, $street2] = $this->splitAddress($actualAddressBlock);

        $resolvedCity = $this->resolveCity($city, null, $actualAddressBlock);

        // Generate prefix if empty
        if (empty($prefix)) {
            $abbrev = $this->generateAbbreviation($resolvedCity);
            $count = Customer::where('company_id', $companyId)->where('type', $type)->count();
            $prefix = $abbrev !== '' ? ($count + 101) . $abbrev : null;
        }

        // Create new customer
        $newCustomer = Customer::create([
            'company_id' => $companyId,
            'name' => $name,
            'phone' => $phone,
            'tax_id' => $gstin,
            'prefix' => $prefix,
            'type' => $type,
        ]);

        $newCustomer->addresses()->create([
            'company_id' => $companyId,
            'name' => $name,
            'address_street_1' => $street1,
            'address_street_2' => $street2,
            'city' => $resolvedCity,
            'state' => '',
            'zip' => '',
            'country_id' => 1,
            'type' => Address::BILLING_TYPE,
        ]);

        $newCustomer->addresses()->create([
            'company_id' => $companyId,
            'name' => $name,
            'address_street_1' => $street1,
            'address_street_2' => $street2,
            'city' => $resolvedCity,
            'state' => '',
            'zip' => '',
            'country_id' => 1,
            'type' => Address::SHIPPING_TYPE,
        ]);

        return $newCustomer;
    }

    private function generateAbbreviation(?string $city): string
    {
        if (empty($city)) {
            return '';
        }
        $cityName = trim(strtoupper($city));

        $dictionary = [
            'UMBERGAON' => 'UMB',
            'UMBARGAON' => 'UMB',
            'VAPI' => 'VAPI',
            'SURAT' => 'SURAT',
            'MUMBAI' => 'MUM',
            'DAMAN' => 'DAM',
            'SILVASSA' => 'SIL',
            'AHMEDABAD' => 'AMD',
        ];

        if (isset($dictionary[$cityName])) {
            return $dictionary[$cityName];
        } elseif (strlen($cityName) <= 4) {
            return $cityName;
        } else {
            return substr($cityName, 0, 3);
        }
    }

    private function resolveCity(?string $specifiedCity, ?string $fallbackPlace, ?string $address): ?string
    {
        $city = trim((string) $specifiedCity);
        if ($city !== '') {
            return $city;
        }

        $fallbackPlace = trim((string) $fallbackPlace);
        if ($fallbackPlace !== '') {
            return $fallbackPlace;
        }

        return null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'invoice_date' => [
                'required',
            ],
            'due_date' => [
                'nullable',
            ],
            'customer_id' => [
                'required',
            ],
            'consignee_customer_id' => [
                'nullable',
            ],
            'invoice_number' => [
                'required',
                Rule::unique('invoices')
                    ->where('company_id', $this->header('company'))
                    ->where('template_name', $this->input('template_name', 'office_invoice')),
            ],
            'exchange_rate' => [
                'nullable',
            ],
            'discount' => [
                'numeric',
                'required',
            ],
            'discount_val' => [
                'integer',
                'required',
            ],
            'sub_total' => [
                'numeric',
                'required',
            ],
            'total' => [
                'numeric',
                'max:999999999999',
                'required',
            ],
            'tax' => [
                'required',
            ],
            'template_name' => [
                'required',
                Rule::in(['office_invoice', 'lr_receipt', 'lorry_receipt']),
            ],
            'items' => [
                'required',
                'array',
            ],
            'items.*' => [
                'required',
                'max:255',
            ],
            'items.*.description' => [
                'nullable',
            ],
            'items.*.name' => [
                Rule::requiredIf($this->input('template_name') !== 'lorry_receipt'),
            ],
            'items.*.quantity' => [
                'numeric',
                'required',
            ],
            'items.*.price' => [
                'numeric',
                'required',
            ],
            'lorry_documents' => [
                'nullable',
                'array',
            ],
            'lorry_documents.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'lorry_documents.*.data' => [
                'nullable',
                'string',
            ],
        ];

        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));

        $customer = Customer::find($this->customer_id);

        if ($customer && $companyCurrency && ! in_array($this->input('template_name'), ['lorry_receipt', 'lr_receipt'])) {
            $customerCurrency = $customer->currency_id ?: $companyCurrency;
            if ((string) $customerCurrency !== (string) $companyCurrency) {
                $rules['exchange_rate'] = [
                    'required',
                ];
            }
        }

        if ($this->isMethod('PUT')) {
            $rules['invoice_number'] = [
                'required',
                Rule::unique('invoices')
                    ->ignore($this->route('invoice')->id)
                    ->where('company_id', $this->header('company'))
                    ->where('template_name', $this->input('template_name', 'office_invoice')),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        $templateName = $this->input('template_name');

        if ($templateName === 'lorry_receipt') {
            return [
                'invoice_number.required' => 'The challan number field is required.',
                'invoice_number.unique' => 'The challan number has already been taken.',
            ];
        }

        if ($templateName === 'lr_receipt') {
            return [
                'invoice_number.required' => 'The docket number field is required.',
                'invoice_number.unique' => 'The docket number has already been taken.',
            ];
        }

        return [];
    }

    public function attributes(): array
    {
        $templateName = $this->input('template_name');

        if ($templateName === 'lorry_receipt') {
            return [
                'invoice_number' => 'challan number',
            ];
        }

        if ($templateName === 'lr_receipt') {
            return [
                'invoice_number' => 'docket number',
            ];
        }

        return [
            'invoice_number' => 'invoice number',
        ];
    }

    public function getInvoicePayload(): array
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? ($this->exchange_rate ?: 1) : 1;
        $customer = Customer::find($this->customer_id);
        $currency = $customer?->currency_id ?: ($current_currency ?: $company_currency);
        $lorryReceiptFinalAmount = $this->lorryReceiptFinalAmountPayable();
        $invoiceTotal = $lorryReceiptFinalAmount !== null ? $lorryReceiptFinalAmount * 100 : (float) $this->total;
        $invoiceSubTotal = $lorryReceiptFinalAmount !== null ? $lorryReceiptFinalAmount * 100 : (float) $this->sub_total;

        $existingInvoice = $this->route('invoice');

        return collect($this->except('items', 'taxes', 'lorry_documents'))
            ->merge([
                'status' => $this->invoiceStatus($existingInvoice),
                'paid_status' => $this->invoicePaidStatus($existingInvoice),
                'company_id' => $this->header('company'),
                'tax_per_item' => CompanySetting::getSetting('tax_per_item', $this->header('company')) ?? 'NO ',
                'discount_per_item' => CompanySetting::getSetting('discount_per_item', $this->header('company')) ?? 'NO',
                'sub_total' => $invoiceSubTotal,
                'total' => $invoiceTotal,
                'due_amount' => $invoiceTotal,
                'sent' => $this->invoiceSent($existingInvoice),
                'viewed' => $this->invoiceViewed($existingInvoice),
                'exchange_rate' => $exchange_rate,
                'base_total' => $invoiceTotal * $exchange_rate,
                'base_discount_val' => $this->discount_val * $exchange_rate,
                'base_sub_total' => $invoiceSubTotal * $exchange_rate,
                'base_tax' => $this->tax * $exchange_rate,
                'base_due_amount' => $invoiceTotal * $exchange_rate,
                'currency_id' => $currency,
            ])
            ->toArray();
    }

    private function lorryReceiptFinalAmountPayable(): ?float
    {
        if ($this->input('template_name') !== Invoice::TEMPLATE_LORRY_RECEIPT) {
            return null;
        }

        $netAmount = $this->amountFromCustomFields([
            'Net Amount Payable',
        ]);

        if ($netAmount !== null && $netAmount > 0) {
            return $netAmount;
        }

        $detentionAmount = $this->amountFromCustomFields(['Add Detention Rs.', 'Detention Amount']);
        $extraHireAmount = $this->amountFromCustomFields(['Extra Hire Rs', 'Extra Hire Amount']);
        $finalOtherAmount = $this->amountFromCustomFields(['Other Rs', 'Final Other Amount']);
        $lessAdvanceOtherBranchAmount = $this->amountFromCustomFields(['Less Adv. at other branch', 'Less Advance Other Branch Amount']);
        $lessDeductionClaimsAmount = $this->amountFromCustomFields(['Less Deduction for Claims', 'Less Deduction Claims Amount']);
        $hasFinalPaymentOperation = collect([
            $detentionAmount,
            $extraHireAmount,
            $finalOtherAmount,
            $lessAdvanceOtherBranchAmount,
            $lessDeductionClaimsAmount,
        ])->contains(fn ($value): bool => $value !== null);

        if (! $hasFinalPaymentOperation) {
            return null;
        }

        $grossHire = $this->sumAmounts([
            $this->amountFromCustomFields(['Lorry Hire', 'Lorry Hire Amount']),
            $this->amountFromCustomFields(['Add Other Charges', 'Other Charges Amount']),
        ]) ?? $this->amountFromCustomFields(['Gross Hire Rupees', 'Gross Hire Amount']);

        $balancePayable = $grossHire !== null
            ? $grossHire - ($this->amountFromCustomFields(['Advance Paid Rs', 'Advance Amount']) ?? 0)
            : $this->amountFromCustomFields(['Balance Amount', 'Balance Rupees']);

        $extraTotal = $this->sumAmounts([
            $detentionAmount,
            $extraHireAmount,
            $finalOtherAmount,
        ]) ?? $this->amountFromCustomFields(['Final Total Extra Amount']);

        $grandTotal = $this->sumAmounts([$balancePayable, $extraTotal])
            ?? $this->amountFromCustomFields(['Grand Total']);

        if ($grandTotal === null) {
            return null;
        }

        $deductionTotal = $this->sumAmounts([
            $lessAdvanceOtherBranchAmount,
            $lessDeductionClaimsAmount,
        ]) ?? $this->amountFromCustomFields(['Total Less Amount']);

        return $grandTotal - ($deductionTotal ?? 0);
    }

    /**
     * @param  array<int, float|null>  $values
     */
    private function sumAmounts(array $values): ?float
    {
        $values = collect($values)->filter(fn ($value): bool => $value !== null);

        if ($values->isEmpty()) {
            return null;
        }

        return (float) $values->sum();
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function amountFromCustomFields(array $labels): ?float
    {
        $value = trim((string) $this->customFieldValue($labels));

        if ($value === '') {
            return null;
        }

        $number = str_replace(',', '', $value);

        if (! is_numeric($number)) {
            return null;
        }

        return (float) $number;
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function customFieldValue(array $labels): mixed
    {
        $normalizedLabels = collect($labels)
            ->map(fn (string $label): string => $this->normalizeLabel($label))
            ->all();

        $matchedValue = null;

        foreach ($this->input('customFields', []) as $field) {
            $label = $field['label']
                ?? $field['name']
                ?? $field['custom_field']['label']
                ?? $field['custom_field']['name']
                ?? '';

            if (! in_array($this->normalizeLabel($label), $normalizedLabels, true)) {
                continue;
            }

            $value = $field['value'] ?? $field['default_answer'] ?? null;

            if (trim((string) $value) !== '') {
                return $value;
            }

            $matchedValue = $value;
        }

        return $matchedValue;
    }

    private function normalizeLabel(?string $label): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
    }

    private function invoiceStatus(?Invoice $invoice): string
    {
        if ($this->has('invoiceSend')) {
            return Invoice::STATUS_SENT;
        }

        return $invoice?->status ?? Invoice::STATUS_DRAFT;
    }

    private function invoicePaidStatus(?Invoice $invoice): string
    {
        return $invoice?->paid_status ?? Invoice::STATUS_UNPAID;
    }

    private function invoiceSent(?Invoice $invoice): bool
    {
        if ($this->has('invoiceSend')) {
            return true;
        }

        if ($this->has('sent')) {
            return $this->boolean('sent');
        }

        return (bool) ($invoice?->sent ?? false);
    }

    private function invoiceViewed(?Invoice $invoice): bool
    {
        if ($this->has('viewed')) {
            return $this->boolean('viewed');
        }

        return (bool) ($invoice?->viewed ?? false);
    }
}
