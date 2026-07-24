<?php

namespace App\Services\FlightSearch;

use App\Models\MockFlightOffer;
use Illuminate\Support\Collection;

class FlightSearchService
{
    public function search(array $params): Collection
    {
        $origin = strtoupper($params['origin']);
        $destination = strtoupper($params['destination']);
        $cabin = $params['cabin'] ?? null;
        $passengers = max(1, (int) ($params['passengers'] ?? 1));
        $tripType = $params['trip_type'] ?? 'oneway';

        return MockFlightOffer::query()
            ->with(['airline', 'originAirport', 'destinationAirport', 'cabinClass'])
            ->where('is_active', true)
            ->whereHas('originAirport', fn ($q) => $q->where('code', $origin))
            ->whereHas('destinationAirport', fn ($q) => $q->where('code', $destination))
            ->when($cabin, fn ($q) => $q->whereHas('cabinClass', fn ($q2) => $q2->whereRaw('LOWER(name) = ?', [strtolower($cabin)])))
            ->orderBy('price_oneway')
            ->get()
            ->map(fn (MockFlightOffer $offer) => $this->present($offer, $tripType, $passengers));
    }

    private function present(MockFlightOffer $offer, string $tripType, int $passengers): array
    {
        $unitPrice = $tripType === 'roundtrip'
            ? (float) ($offer->price_roundtrip ?? $offer->price_oneway * 1.85)
            : (float) $offer->price_oneway;

        return [
            'id' => $offer->id,
            'airline' => $offer->airline?->name,
            'airline_code' => $offer->airline?->code,
            'origin' => $offer->originAirport?->code,
            'origin_name' => $offer->originAirport?->name,
            'destination' => $offer->destinationAirport?->code,
            'destination_name' => $offer->destinationAirport?->name,
            'cabin_class' => $offer->cabinClass?->name,
            'trip_type' => $tripType,
            'currency' => $offer->currency,
            'unit_price' => round($unitPrice, 2),
            'total_price' => round($unitPrice * $passengers, 2),
            'passengers' => $passengers,
            'duration_minutes' => $offer->duration_minutes,
        ];
    }
}
