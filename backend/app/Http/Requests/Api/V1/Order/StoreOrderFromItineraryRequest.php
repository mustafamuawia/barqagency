<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderFromItineraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after:departure_date',

            'itinerary' => 'required|array',
            'itinerary.ignav_id' => 'nullable|string',
            'itinerary.cabin_class' => 'required|string',
            'itinerary.currency' => 'required|string|size:3',
            'itinerary.total_price' => 'required|numeric|min:0',
            'itinerary.outbound' => 'required|array',
            'itinerary.outbound.carrier' => 'nullable|string',
            'itinerary.outbound.segments' => 'required|array|min:1',
            'itinerary.outbound.segments.*.marketing_carrier_code' => 'required|string',
            'itinerary.outbound.segments.*.operating_carrier_name' => 'nullable|string',
            'itinerary.outbound.segments.*.departure_airport' => 'required|string|size:3',
            'itinerary.outbound.segments.*.arrival_airport' => 'required|string|size:3',
            'itinerary.outbound.segments.*.departure_time_local' => 'required|string',
            'itinerary.outbound.segments.*.arrival_time_local' => 'required|string',
            'itinerary.outbound.segments.*.flight_number' => 'nullable|string',
            'itinerary.outbound.segments.*.aircraft' => 'nullable|string',
            'itinerary.outbound.segments.*.duration_minutes' => 'nullable|integer',
            'itinerary.inbound' => 'nullable|array',
            'itinerary.inbound.segments' => 'required_with:itinerary.inbound|array',
            'itinerary.inbound.segments.*.marketing_carrier_code' => 'required_with:itinerary.inbound|string',
            'itinerary.inbound.segments.*.operating_carrier_name' => 'nullable|string',
            'itinerary.inbound.segments.*.departure_airport' => 'required_with:itinerary.inbound|string|size:3',
            'itinerary.inbound.segments.*.arrival_airport' => 'required_with:itinerary.inbound|string|size:3',
            'itinerary.inbound.segments.*.departure_time_local' => 'required_with:itinerary.inbound|string',
            'itinerary.inbound.segments.*.arrival_time_local' => 'required_with:itinerary.inbound|string',
            'itinerary.inbound.segments.*.flight_number' => 'nullable|string',
            'itinerary.inbound.segments.*.aircraft' => 'nullable|string',
            'itinerary.inbound.segments.*.duration_minutes' => 'nullable|integer',

            'passengers' => 'required|array|min:1',
            'passengers.*.passenger_type_id' => 'required|exists:passenger_types,id',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name' => 'required|string|max:100',
        ];
    }
}
