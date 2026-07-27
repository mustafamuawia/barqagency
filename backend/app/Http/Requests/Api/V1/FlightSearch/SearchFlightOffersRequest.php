<?php

namespace App\Http\Requests\Api\V1\FlightSearch;

use Illuminate\Foundation\Http\FormRequest;

class SearchFlightOffersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin' => 'required|string|size:3',
            'destination' => 'required|string|size:3|different:origin',
            'trip_type' => 'sometimes|in:oneway,roundtrip,multi',
            'cabin' => 'sometimes|string|in:economy,premium_economy,business,first',
            'passengers' => 'sometimes|integer|min:1|max:9',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required_if:trip_type,roundtrip|nullable|date|after_or_equal:departure_date',
        ];
    }
}
