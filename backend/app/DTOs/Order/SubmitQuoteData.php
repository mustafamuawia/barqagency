<?php

namespace App\DTOs\Order;

use App\Http\Requests\Api\V1\Order\SubmitQuoteRequest;

final readonly class SubmitQuoteData
{
    /**
     * @param  array<int, array{id: int, departure_datetime?: ?string, departure_terminal?: ?string}>  $flights
     */
    public function __construct(
        public float $basePrice,
        public float $taxes,
        public float $fees,
        public float $totalPrice,
        public ?string $remarks,
        public array $flights = [],
        public ?string $returnDate = null,
    ) {
    }

    public static function fromRequest(SubmitQuoteRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            basePrice: $validated['base_price'],
            taxes: $validated['taxes'] ?? 0,
            fees: $validated['fees'] ?? 0,
            totalPrice: $validated['total_price'],
            remarks: $validated['remarks'] ?? null,
            flights: $validated['flights'] ?? [],
            returnDate: $validated['return_date'] ?? null,
        );
    }
}
