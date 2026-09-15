<?php

namespace App\Http\Resources\Api\V1\Settings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'bank_name' => $this->resource->bank_name,
            'account_name' => $this->resource->account_name,
            'account_number' => $this->resource->account_number,
            'iban' => $this->resource->iban,
            'is_active' => (bool) $this->resource->is_active,
            'sort_order' => (int) $this->resource->sort_order,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
