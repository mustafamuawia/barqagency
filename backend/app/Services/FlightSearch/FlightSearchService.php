<?php

namespace App\Services\FlightSearch;

use Illuminate\Support\Collection;

class FlightSearchService
{
    public function __construct(private readonly IgnavClient $ignav) {}

    public function search(array $params): Collection
    {
        $origin = strtoupper($params['origin']);
        $destination = strtoupper($params['destination']);
        $cabin = $params['cabin'] ?? 'economy';
        $passengers = max(1, (int) ($params['passengers'] ?? 1));
        $tripType = $params['trip_type'] ?? 'oneway';
        $isRoundtrip = $tripType === 'roundtrip';

        $payload = [
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $params['departure_date'],
            'adults' => $passengers,
            'cabin_class' => $cabin,
            'market' => config('services.ignav.market'),
        ];

        $response = $isRoundtrip
            ? $this->ignav->roundTrip($payload + ['return_date' => $params['return_date']])
            : $this->ignav->oneWay($payload);

        $itineraries = $response['itineraries'] ?? [];

        return collect($itineraries)->map(
            fn (array $itinerary) => $this->present($itinerary, $origin, $destination, $tripType, $passengers)
        );
    }

    private function present(array $itinerary, string $origin, string $destination, string $tripType, int $passengers): array
    {
        $markup = 1 + (config('services.ignav.markup_percent', 0) / 100);
        $amount = (float) ($itinerary['price']['amount'] ?? 0) * $markup;
        $outbound = $itinerary['outbound'] ?? [];
        $inbound = $itinerary['inbound'] ?? null;
        $firstSegment = $outbound['segments'][0] ?? [];

        $durationMinutes = ($outbound['duration_minutes'] ?? 0) + ($inbound['duration_minutes'] ?? 0);

        return [
            'ignav_id' => $itinerary['ignav_id'] ?? null,
            'airline' => $outbound['carrier'] ?? $firstSegment['operating_carrier_name'] ?? null,
            'airline_code' => $firstSegment['marketing_carrier_code'] ?? null,
            'origin' => $origin,
            'destination' => $destination,
            'cabin_class' => $itinerary['cabin_class'] ?? 'economy',
            'trip_type' => $tripType,
            'currency' => $itinerary['price']['currency'] ?? 'USD',
            'unit_price' => round($amount / $passengers, 2),
            'total_price' => round($amount, 2),
            'passengers' => $passengers,
            'duration_minutes' => $durationMinutes ?: null,
            'requires_self_transfer' => $itinerary['requires_self_transfer'] ?? false,
            'outbound' => $outbound,
            'inbound' => $inbound,
        ];
    }
}
