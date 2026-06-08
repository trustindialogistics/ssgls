<?php

namespace App\Http\Requests;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
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
            'payment_date' => [
                'required',
            ],
            'customer_id' => [
                'required',
            ],
            'exchange_rate' => [
                'nullable',
            ],
            'amount' => [
                'required',
            ],
            'tds_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'deduction_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'invoice_paid_status' => [
                'nullable',
                Rule::in([
                    Invoice::STATUS_UNPAID,
                    Invoice::STATUS_PARTIALLY_PAID,
                    Invoice::STATUS_PAID,
                ]),
            ],
            'payment_number' => [
                'required',
                Rule::unique('payments')->where('company_id', $this->header('company')),
            ],
            'invoice_id' => [
                'nullable',
                Rule::exists('invoices', 'id')
                    ->where('company_id', $this->header('company'))
                    ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE),
            ],
            'payment_method_id' => [
                'nullable',
            ],
            'notes' => [
                'nullable',
            ],
            'allocations' => [
                'nullable',
                'array',
            ],
            'allocations.*.invoice_id' => [
                'required_with:allocations',
                Rule::exists('invoices', 'id')
                    ->where('company_id', $this->header('company'))
                    ->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE),
            ],
            'allocations.*.amount' => [
                'required_with:allocations',
                'integer',
                'min:0',
            ],
            'allocations.*.tds_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'allocations.*.deduction_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'allocations.*.invoice_paid_status' => [
                'nullable',
                Rule::in([
                    Invoice::STATUS_UNPAID,
                    Invoice::STATUS_PARTIALLY_PAID,
                    Invoice::STATUS_PAID,
                ]),
            ],
        ];

        if ($this->isMethod('PUT')) {
            $rules['payment_number'] = [
                'required',
                Rule::unique('payments')
                    ->ignore($this->route('payment')->id)
                    ->where('company_id', $this->header('company')),
            ];
        }

        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));

        $customer = Customer::find($this->customer_id);

        if ($customer && $companyCurrency) {
            if ((string) $customer->currency_id !== $companyCurrency) {
                $rules['exchange_rate'] = [
                    'required',
                ];
            }
        }

        return $rules;
    }

    public function getPaymentPayload()
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? $this->exchange_rate : 1;
        $currency = Customer::find($this->customer_id)->currency_id;

        $validated = collect($this->validated());
        $hasDeduction = ((int) $this->tds_amount > 0) || ((int) $this->deduction_amount > 0);

        if (! $hasDeduction) {
            $validated['invoice_paid_status'] = null;
        }

        return $validated
            ->merge([
                'creator_id' => $this->user()->id,
                'company_id' => $this->header('company'),
                'exchange_rate' => $exchange_rate,
                'base_amount' => $this->amount * $exchange_rate,
                'currency_id' => $currency,
            ])
            ->toArray();
    }
}
