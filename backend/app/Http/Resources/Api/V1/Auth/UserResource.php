<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('userType');
        $agency = $this->resource->agencies()->first();

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'user_type' => $this->resource->userType?->name,
            'agency_id' => $agency?->id,
            'agency_status' => $agency?->status,
            'agency_rejection_reason' => $agency?->rejection_reason,
            'email_verified' => (bool) $this->resource->email_verified_at,
            'roles' => [],
            'permissions' => [],
        ];
    }
}
