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
            // Every segment field needs a rule: as soon as one `segments.*` rule exists,
            // validated() returns only the nested keys that were validated.
            'segments' => 'required|array|min:1',
            // A segment names its airline either by id (picked from the list) or by name,
            // which is registered as a new carrier when it is not on the list yet.
            'segments.*.airline_id' => 'nullable|integer|exists:airlines,id',
            'segments.*.airline_name' => 'required_without:segments.*.airline_id|nullable|string|max:255',
            'segments.*.departure_airport_id' => 'required|integer|exists:airports,id',
            'segments.*.arrival_airport_id' => 'required|integer|exists:airports,id',
            'segments.*.cabin_class_id' => 'required|integer|exists:cabin_classes,id',
            'segments.*.departure_datetime' => 'nullable|date',
            'segments.*.arrival_datetime' => 'nullable|date',
            'segments.*.flight_number' => 'nullable|string|max:20',
            'segments.*.sequence' => 'nullable|integer|min:1',
            'passengers' => 'required|array|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'segments.*.airline_name.required_without' => 'يرجى إدخال اسم شركة الطيران.',
        ];
    }
}
