<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LorryReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_customer_id' => ['nullable', 'integer'],
            'driver_customer_id' => ['nullable', 'integer'],
            'broker_customer_id' => ['nullable', 'integer'],
            '*' => ['nullable'],
        ];
    }
}
