<?php

namespace App\DTOs\Order;

use App\Http\Requests\Api\V1\Order\StoreOrderFromItineraryRequest;

final readonly class BookItineraryData
{
    public function __construct(
        public string $departureDate,
        public ?string $returnDate,
        public array $itinerary,
        public array $passengers,
    ) {
    }

    public static function fromRequest(StoreOrderFromItineraryRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            departureDate: $validated['departure_date'],
            returnDate: $validated['return_date'] ?? null,
            itinerary: $validated['itinerary'],
            passengers: $validated['passengers'],
        );
    }
}
