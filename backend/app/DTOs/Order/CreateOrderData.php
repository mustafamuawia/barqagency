<?php

namespace App\DTOs\Order;

use App\Http\Requests\Api\V1\Order\StoreOrderRequest;

final readonly class CreateOrderData
{
    public function __construct(
        public ?int $agencyId,
        public string $tripType,
        public string $departureDate,
        public ?string $returnDate,
        public int $totalPassengers,
        public array $segments,
        public array $passengers,
    ) {
    }

    public static function fromRequest(StoreOrderRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            agencyId: $validated['agency_id'] ?? null,
            tripType: $validated['trip_type'],
            departureDate: $validated['departure_date'],
            returnDate: $validated['return_date'] ?? null,
            totalPassengers: $validated['total_passengers'],
            segments: $validated['segments'],
            passengers: $validated['passengers'],
        );
    }
}
