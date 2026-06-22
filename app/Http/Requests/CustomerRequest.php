<?php

namespace App\Http\Requests;

use App\Models\Address;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->is('*consignees*')) {
            $this->merge(['type' => \App\Models\Customer::TYPE_CONSIGNEE]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                function ($attribute, $value, $fail) {
                    $companyId = $this->header('company');
                    $type = $this->input('type') 
                        ?? ($this->route('customer') ? $this->route('customer')->type : null)
                        ?? ($this->is('*consignees*') ? \App\Models\Customer::TYPE_CONSIGNEE : \App\Models\Customer::TYPE_CUSTOMER);
                    
                    if (!in_array($type, [\App\Models\Customer::TYPE_CUSTOMER, \App\Models\Customer::TYPE_CONSIGNEE])) {
                        return;
                    }

                    $city = $this->input('billing.city');
                    $gstin = trim((string) $this->input('tax_id'));

                    $query = \App\Models\Customer::where('company_id', $companyId)
                        ->where('type', $type)
                        ->where(function ($q) use ($value) {
                            $q->where('name', $value)
                              ->orWhereRaw('LOWER(name) = ?', [strtolower($value)]);
                        });

                    if ($this->route('customer')) {
                        $query->where('id', '!=', $this->route('customer')->id);
                    }

                    if ($gstin !== '') {
                        $existsGstin = (clone $query)->where('tax_id', $gstin)->exists();
                        if ($existsGstin) {
                            $fail('A customer with this GSTIN already exists.');
                            return;
                        }
                    }

                    if (!empty($city)) {
                        $existsNameCity = $query->whereHas('billingAddress', function ($q) use ($city) {
                            $q->whereRaw('LOWER(city) = ?', [strtolower(trim($city))]);
                        })->exists();

                        if ($existsNameCity) {
                            $fail('A customer with this Name and City already exists.');
                        }
                    }
                }
            ],
            'type' => [
                'nullable',
                Rule::in([
                    \App\Models\Customer::TYPE_CUSTOMER,
                    \App\Models\Customer::TYPE_CONSIGNEE,
                    \App\Models\Customer::TYPE_OWNER,
                    \App\Models\Customer::TYPE_DRIVER,
                    \App\Models\Customer::TYPE_BROKER,
                ]),
            ],
            'email' => [
                'email',
                'nullable',
                Rule::unique('customers')->where('company_id', $this->header('company')),
            ],
            'password' => [
                'nullable',
            ],
            'phone' => [
                'nullable',
            ],
            'company_name' => [
                'nullable',
            ],
            'contact_name' => [
                'nullable',
            ],
            'website' => [
                'nullable',
            ],
            'prefix' => [
                'nullable',
            ],
            'tax_id' => [
                'nullable',
            ],
            'enable_portal' => [
                'boolean',
            ],
            'currency_id' => [
                'nullable',
            ],
            'billing.name' => [
                'nullable',
            ],
            'billing.address_street_1' => [
                'nullable',
            ],
            'billing.address_street_2' => [
                'nullable',
            ],
            'billing.city' => [
                Rule::requiredIf(fn () => in_array(
                    $this->input('type') 
                        ?? ($this->route('customer') ? $this->route('customer')->type : null)
                        ?? ($this->is('*consignees*') ? \App\Models\Customer::TYPE_CONSIGNEE : \App\Models\Customer::TYPE_CUSTOMER),
                    [\App\Models\Customer::TYPE_CUSTOMER, \App\Models\Customer::TYPE_CONSIGNEE]
                )),
                'nullable',
            ],
            'billing.state' => [
                'nullable',
            ],
            'billing.country_id' => [
                'nullable',
            ],
            'billing.zip' => [
                'nullable',
            ],
            'billing.phone' => [
                'nullable',
            ],
            'billing.fax' => [
                'nullable',
            ],
            'shipping.name' => [
                'nullable',
            ],
            'shipping.address_street_1' => [
                'nullable',
            ],
            'shipping.address_street_2' => [
                'nullable',
            ],
            'shipping.city' => [
                'nullable',
            ],
            'shipping.state' => [
                'nullable',
            ],
            'shipping.country_id' => [
                'nullable',
            ],
            'shipping.zip' => [
                'nullable',
            ],
            'shipping.phone' => [
                'nullable',
            ],
            'shipping.fax' => [
                'nullable',
            ],
        ];

        if ($this->isMethod('PUT') && $this->email != null) {
            $rules['email'] = [
                'email',
                'nullable',
                Rule::unique('customers')->where('company_id', $this->header('company'))->ignore($this->route('customer')->id),
            ];
        }

        return $rules;
    }

    public function getCustomerPayload()
    {
        return collect($this->validated())
            ->only([
                'name',
                'email',
                'currency_id',
                'password',
                'phone',
                'prefix',
                'tax_id',
                'type',
                'company_name',
                'contact_name',
                'website',
                'enable_portal',
                'estimate_prefix',
                'payment_prefix',
                'invoice_prefix',
            ])
            ->merge([
                'creator_id' => $this->user()->id,
                'company_id' => $this->header('company'),
            ])
            ->toArray();
    }

    public function getShippingAddress()
    {
        return collect($this->shipping)
            ->merge([
                'type' => Address::SHIPPING_TYPE,
            ])
            ->toArray();
    }

    public function getBillingAddress()
    {
        return collect($this->billing)
            ->merge([
                'type' => Address::BILLING_TYPE,
            ])
            ->toArray();
    }

    public function hasAddress(array $address)
    {
        $data = Arr::where($address, function ($value, $key) {
            return isset($value);
        });

        return $data;
    }
}
