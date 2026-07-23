<?php

namespace App\DTOs\Refund;

use App\Http\Requests\Api\V1\Refund\StoreRefundRequest;

final readonly class CreateRefundData
{
    public function __construct(
        public int $orderId,
        public string $reason,
    ) {
    }

    public static function fromRequest(StoreRefundRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            orderId: $validated['order_id'],
            reason: $validated['reason'],
        );
    }
}
