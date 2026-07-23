<?php

namespace App\Http\Requests\Api\V1\Refund;

use Illuminate\Foundation\Http\FormRequest;

class RejectRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_remarks' => 'required|string|max:1000',
        ];
    }
}
