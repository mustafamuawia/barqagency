<?php

namespace App\DTOs\Order;

use App\Http\Requests\Api\V1\Order\StoreOrderRequest;

final readonly class CreateOrderData
{
    /**
     * @param array<int, array{passenger_type_id: int, count: int}> $passengerCounts
     */
    public function __construct(
        public ?int $agencyId,
        public string $tripType,
        public string $departureDate,
        public ?string $returnDate,
        public array $segments,
        public array $passengerCounts,
        public ?int $weight = null,
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
            segments: $validated['segments'],
            // A type the agency zeroed out carries nobody, so it should not reach the order.
            passengerCounts: array_values(array_filter(
                $validated['passenger_counts'],
                fn (array $entry) => $entry['count'] > 0
            )),
            weight: $validated['weight'] ?? null,
        );
    }

    public function totalPassengers(): int
    {
        return array_sum(array_column($this->passengerCounts, 'count'));
    }
}
