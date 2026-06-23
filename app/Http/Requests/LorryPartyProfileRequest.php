<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LorryPartyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in(['OWNER', 'DRIVER', 'BROKER'])],
            'code' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'financer_name' => ['nullable', 'string'],
            'financer_address' => ['nullable', 'string'],
            'place' => ['nullable', 'string'],
            'licence_no' => ['nullable', 'string'],
            'licence_date' => ['nullable', 'string'],
            'licence_issued_by' => ['nullable', 'string'],
            'rto_address' => ['nullable', 'string'],
            'valid_up_to' => ['nullable', 'string'],
            'advice_no' => ['nullable', 'string'],
            'advice_date' => ['nullable', 'string'],
            'destination_broker_name' => ['nullable', 'string'],
            'destination_broker_address' => ['nullable', 'string'],
            'bank_account_no' => ['nullable', 'string'],
            'rc_front_path' => ['nullable', 'string'],
            'rc_back_path' => ['nullable', 'string'],
            'pan_front_path' => ['nullable', 'string'],
            'insurance_path' => ['nullable', 'string'],
            'license_front_path' => ['nullable', 'string'],
            'license_back_path' => ['nullable', 'string'],
            'pan_front_path_broker' => ['nullable', 'string'],
        ];
    }
}
