<?php

namespace App\Http\Requests\Api\V1\Agency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,suspended,inactive',
            'credit_limit' => 'sometimes|numeric|min:0',
        ];
    }
}
