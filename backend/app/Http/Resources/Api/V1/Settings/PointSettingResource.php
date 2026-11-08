<?php

namespace App\Http\Resources\Api\V1\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'money_amount'             => (float) $this->resource->money_amount,
            'point_amount'             => (float) $this->resource->point_amount,
            'agency_commission_amount' => (float) $this->resource->agency_commission_amount,
            'usd_to_sar_rate'          => (float) $this->resource->usd_to_sar_rate,
            'updated_at'               => $this->resource->updated_at,
        ];
    }
}
