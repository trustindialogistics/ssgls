<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                Rule::unique('companies')->ignore($this->header('company'), 'id'),
            ],
            'vat_id' => [
                'nullable',
            ],
            'tax_id' => [
                'nullable',
            ],
            'gstin' => [
                'nullable',
                'string',
                'max:255',
            ],
            'enrollment_no' => [
                'nullable',
                'string',
                'max:255',
            ],
            'pan_no' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tagline' => [
                'nullable',
                'string',
                'max:255',
            ],
            'top_heading' => [
                'nullable',
                'string',
                'max:255',
            ],
            'billing_branch_name_address' => [
                'nullable',
                'string',
            ],
            'notification_email' => [
                'nullable',
                'email',
            ],
            'slug' => [
                'nullable',
            ],
            'address.country_id' => [
                'nullable',
            ],
        ];
    }

    public function getCompanyPayload()
    {
        return collect($this->validated())
            ->only([
                'name',
                'slug',
                'vat_id',
                'tax_id',
                'gstin',
                'enrollment_no',
                'pan_no',
                'tagline',
                'top_heading',
                'billing_branch_name_address',
            ])
            ->toArray();
    }

    public function getCompanySettingsPayload(): array
    {
        return collect($this->validated())
            ->only([
                'notification_email',
            ])
            ->toArray();
    }
}
