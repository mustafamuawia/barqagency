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
            'cabin' => 'sometimes|string',
            'passengers' => 'sometimes|integer|min:1|max:9',
        ];
    }
}
