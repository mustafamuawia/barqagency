<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_price' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'fees' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
}
