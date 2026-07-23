<?php

namespace App\Http\Requests\Api\V1\Refund;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string|max:1000',
        ];
    }
}
