<?php

namespace App\Http\Requests;

use App\Rules\Base64Mime;
use Illuminate\Foundation\Http\FormRequest;

class UploadInvoicePodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pod' => [
                'required',
                new Base64Mime(['gif', 'jpg', 'jpeg', 'png']),
            ],
        ];
    }
}
