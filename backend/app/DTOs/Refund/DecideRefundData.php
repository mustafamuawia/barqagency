<?php

namespace App\DTOs\Refund;

use App\Http\Requests\Api\V1\Refund\ApproveRefundRequest;

final readonly class DecideRefundData
{
    public function __construct(
        public float $penaltyAmount,
        public ?string $adminRemarks,
    ) {
    }

    public static function fromRequest(ApproveRefundRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            penaltyAmount: $validated['penalty_amount'] ?? 0,
            adminRemarks: $validated['admin_remarks'] ?? null,
        );
    }
}
