<?php

namespace App\DTOs\MockFlight;

final readonly class MockFlightOfferData
{
    public function __construct(
        public ?int $airlineId,
        public ?int $originAirportId,
        public ?int $destinationAirportId,
        public ?int $cabinClassId,
        public ?float $priceOneway,
        public ?float $priceRoundtrip,
        public ?string $currency,
        public ?int $durationMinutes,
        public ?bool $isActive,
    ) {
    }

    public static function fromValidated(array $validated): self
    {
        return new self(
            airlineId: $validated['airline_id'] ?? null,
            originAirportId: $validated['origin_airport_id'] ?? null,
            destinationAirportId: $validated['destination_airport_id'] ?? null,
            cabinClassId: $validated['cabin_class_id'] ?? null,
            priceOneway: array_key_exists('price_oneway', $validated) ? (float) $validated['price_oneway'] : null,
            priceRoundtrip: array_key_exists('price_roundtrip', $validated) ? $validated['price_roundtrip'] : null,
            currency: $validated['currency'] ?? null,
            durationMinutes: array_key_exists('duration_minutes', $validated) ? $validated['duration_minutes'] : null,
            isActive: array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
        );
    }

    public function toModelAttributes(): array
    {
        return array_filter([
            'airline_id' => $this->airlineId,
            'origin_airport_id' => $this->originAirportId,
            'destination_airport_id' => $this->destinationAirportId,
            'cabin_class_id' => $this->cabinClassId,
            'price_oneway' => $this->priceOneway,
            'price_roundtrip' => $this->priceRoundtrip,
            'currency' => $this->currency,
            'duration_minutes' => $this->durationMinutes,
            'is_active' => $this->isActive,
        ], fn ($value) => $value !== null);
    }
}
