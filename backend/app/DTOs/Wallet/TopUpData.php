<?php

namespace App\DTOs\Wallet;

use App\Http\Requests\Api\V1\Wallet\TopUpRequest;

final readonly class TopUpData
{
    public function __construct(
        public float $amount,
        public ?string $description,
    ) {
    }

    public static function fromRequest(TopUpRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            amount: $validated['amount'],
            description: $validated['description'] ?? null,
        );
    }
}
