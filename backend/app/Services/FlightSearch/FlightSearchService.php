<?php

namespace App\Services\FlightSearch;

use App\Services\Settings\PointSettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FlightSearchService
{
    // Duffel offers expire in 10–30 minutes; cache 15 min to stay safely inside that window.
    private const OFFER_CACHE_TTL_MINUTES = 15;
    public function __construct(
        private readonly DuffelClient $duffel,
        private readonly PointSettingService $pointSettingService,
    ) {}

    public function search(array $params): Collection
    {
        $origin = strtoupper($params['origin']);
        $destination = strtoupper($params['destination']);
        $cabin = $params['cabin'] ?? 'economy';
        $passengers = max(1, (int) ($params['passengers'] ?? 1));
        $tripType = $params['trip_type'] ?? 'oneway';

        $slices = [
            ['origin' => $origin, 'destination' => $destination, 'departure_date' => $params['departure_date']],
        ];

        if ($tripType === 'roundtrip') {
            $slices[] = [
                'origin' => $destination,
                'destination' => $origin,
                'departure_date' => $params['return_date'],
            ];
        }

        $passengerList = array_fill(0, $passengers, ['type' => 'adult']);

        $offers = $this->duffel->searchOffers($slices, $passengerList, $cabin);

        return collect($offers)->map(
            fn (array $offer) => $this->present($offer, $origin, $destination, $tripType, $passengers)
        );
    }

    private function present(array $offer, string $origin, string $destination, string $tripType, int $passengers): array
    {
        $markup = 1 + (config('services.duffel.markup_percent', 0) / 100);

        // Preserve the exact Duffel amount string — must be sent back unmodified when booking.
        $duffelAmount   = $offer['total_amount'] ?? '0';
        $currency       = strtoupper($offer['total_currency'] ?? 'USD');

        // Displayed price (includes markup) and its SAR equivalent for wallet deduction.
        $displayAmount  = (float) $duffelAmount * $markup;
        $sarAmount      = $this->toSar($displayAmount, $currency);

        $outboundSlice = $offer['slices'][0] ?? [];
        $inboundSlice  = $offer['slices'][1] ?? null;
        $outbound      = $this->mapSlice($outboundSlice);
        $inbound       = $inboundSlice ? $this->mapSlice($inboundSlice) : null;

        // Cache the trusted pricing data server-side so the booking flow never relies on
        // client-supplied amounts. The key expires with the offer so a cache miss == expiry.
        $offerId = $offer['id'] ?? null;
        $passportRequired  = (bool) ($offer['passenger_identity_documents_required'] ?? false);
        $supportedDocTypes = $offer['supported_passenger_identity_document_types'] ?? ['passport'];

        if ($offerId) {
            Cache::put(
                'duffel_offer:' . $offerId,
                [
                    'amount'             => $duffelAmount,
                    'currency'           => $currency,
                    'sar'                => round($sarAmount, 2),
                    'passenger_ids'      => array_column($offer['passengers'] ?? [], 'id'),
                    'passport_required'  => $passportRequired,
                    'supported_doc_types' => $supportedDocTypes,
                ],
                now()->addMinutes(self::OFFER_CACHE_TTL_MINUTES)
            );
        }

        return [
            'offer_id'            => $offerId,
            // These values are displayed in the UI only — the server re-reads them from
            // cache on booking and never accepts client-supplied pricing.
            'duffel_total_amount' => $duffelAmount,
            'duffel_total_currency' => $currency,
            'duffel_passengers'   => array_map(fn ($p) => ['id' => $p['id'], 'type' => $p['type']], $offer['passengers'] ?? []),
            'airline'             => $offer['owner']['name'] ?? ($outbound['carrier'] ?? null),
            'airline_code'        => $offer['owner']['iata_code'] ?? null,
            'origin'              => $origin,
            'destination'         => $destination,
            'cabin_class'         => $offer['slices'][0]['segments'][0]['passengers'][0]['cabin_class_marketing_name']
                ?? $offer['cabin_class']
                ?? 'economy',
            'trip_type'           => $tripType,
            'currency'            => $currency,
            'unit_price'          => round($displayAmount / $passengers, 2),
            'total_price'         => round($displayAmount, 2),
            'unit_price_sar'      => round($sarAmount / $passengers, 2),
            'total_price_sar'     => round($sarAmount, 2),
            'passengers'          => $passengers,
            'duration_minutes'    => (($outbound['duration_minutes'] ?? 0) + ($inbound['duration_minutes'] ?? 0)) ?: null,
            'requires_self_transfer' => false,
            'outbound'            => $outbound,
            'inbound'             => $inbound,
            // Identity document requirements — read from the offer, not from client input.
            'passport_required'   => $passportRequired,
            'supported_doc_types' => $supportedDocTypes,
        ];
    }

    private function mapSlice(array $slice): array
    {
        $segments = array_map([$this, 'mapSegment'], $slice['segments'] ?? []);
        $firstSegment = $segments[0] ?? [];

        return [
            'carrier' => $slice['segments'][0]['marketing_carrier']['name'] ?? null,
            'duration_minutes' => $this->parseDuration($slice['duration'] ?? ''),
            'segments' => $segments,
            'marketing_carrier_code' => $firstSegment['marketing_carrier_code'] ?? null,
            'operating_carrier_name' => $firstSegment['operating_carrier_name'] ?? null,
        ];
    }

    private function mapSegment(array $seg): array
    {
        $carrierCode = $seg['marketing_carrier']['iata_code'] ?? '';
        $flightNum = $seg['marketing_carrier_flight_number'] ?? '';

        return [
            'marketing_carrier_code' => $carrierCode,
            'operating_carrier_name' => $seg['operating_carrier']['name'] ?? $seg['marketing_carrier']['name'] ?? null,
            'departure_airport' => $seg['origin']['iata_code'] ?? null,
            'arrival_airport' => $seg['destination']['iata_code'] ?? null,
            'departure_time_local' => $seg['departing_at'] ?? null,
            'arrival_time_local' => $seg['arriving_at'] ?? null,
            'flight_number' => $carrierCode && $flightNum ? $carrierCode . $flightNum : ($flightNum ?: null),
            'aircraft' => $seg['aircraft']['name'] ?? null,
            'duration_minutes' => $this->parseDuration($seg['duration'] ?? ''),
        ];
    }

    private function toSar(float $amount, string $currency): float
    {
        return $this->pointSettingService->currencyToSar($amount, $currency);
    }

    private function parseDuration(string $iso8601): int
    {
        if (!$iso8601) {
            return 0;
        }
        preg_match('/P(?:(\d+)D)?T(?:(\d+)H)?(?:(\d+)M)?/', $iso8601, $m);
        return ((int) ($m[1] ?? 0) * 24 + (int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
    }
}
