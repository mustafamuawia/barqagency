<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMockFlightOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline_id' => ['sometimes', Rule::exists('airlines', 'id')],
            'origin_airport_id' => ['sometimes', Rule::exists('airports', 'id'), 'different:destination_airport_id'],
            'destination_airport_id' => ['sometimes', Rule::exists('airports', 'id')],
            'cabin_class_id' => ['sometimes', Rule::exists('cabin_classes', 'id')],
            'price_oneway' => 'sometimes|numeric|min:0',
            'price_roundtrip' => 'sometimes|nullable|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'duration_minutes' => 'sometimes|nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
