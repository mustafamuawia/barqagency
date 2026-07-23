<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_id' => 'sometimes|exists:travel_agencies,id',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after:departure_date',
            'total_passengers' => 'required|integer|min:1',
            'segments' => 'required|array|min:1',
            'passengers' => 'required|array|min:1',
        ];
    }
}
