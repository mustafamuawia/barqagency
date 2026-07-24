<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MockFlightOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'airline_id' => $this->resource->airline_id,
            'airline' => $this->resource->airline?->name,
            'origin_airport_id' => $this->resource->origin_airport_id,
            'origin' => $this->resource->originAirport?->code,
            'origin_name' => $this->resource->originAirport?->name,
            'destination_airport_id' => $this->resource->destination_airport_id,
            'destination' => $this->resource->destinationAirport?->code,
            'destination_name' => $this->resource->destinationAirport?->name,
            'cabin_class_id' => $this->resource->cabin_class_id,
            'cabin_class' => $this->resource->cabinClass?->name,
            'price_oneway' => (float) $this->resource->price_oneway,
            'price_roundtrip' => $this->resource->price_roundtrip !== null ? (float) $this->resource->price_roundtrip : null,
            'currency' => $this->resource->currency,
            'duration_minutes' => $this->resource->duration_minutes,
            'is_active' => (bool) $this->resource->is_active,
            'created_at' => $this->resource->created_at,
        ];
    }
}
