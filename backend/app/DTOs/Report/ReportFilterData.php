<?php

namespace App\DTOs\Report;

use App\Http\Requests\Api\V1\Report\ReportFilterRequest;
use Carbon\Carbon;

final readonly class ReportFilterData
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public ?int $agencyId,
    ) {
    }

    public static function fromRequest(ReportFilterRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            from: Carbon::parse($validated['from'])->startOfDay(),
            to: Carbon::parse($validated['to'])->endOfDay(),
            agencyId: $validated['agency_id'] ?? null,
        );
    }
}
