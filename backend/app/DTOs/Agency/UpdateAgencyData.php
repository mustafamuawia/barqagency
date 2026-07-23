<?php

namespace App\DTOs\Agency;

use App\Http\Requests\Api\V1\Agency\UpdateAgencyRequest;

final readonly class UpdateAgencyData
{
    public function __construct(
        public ?string $name,
        public ?string $phone,
        public ?string $address,
        public ?string $status,
        public ?float $creditLimit,
    ) {
    }

    public static function fromRequest(UpdateAgencyRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? null,
            phone: array_key_exists('phone', $validated) ? $validated['phone'] : null,
            address: array_key_exists('address', $validated) ? $validated['address'] : null,
            status: $validated['status'] ?? null,
            creditLimit: $validated['credit_limit'] ?? null,
        );
    }

    public function toModelAttributes(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'credit_limit' => $this->creditLimit,
        ], fn ($value) => $value !== null);
    }
}
