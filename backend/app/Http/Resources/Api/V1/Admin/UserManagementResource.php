<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'user_type_id' => $this->resource->user_type_id,
            'user_type' => $this->resource->userType?->name,
            'user_type_label' => $this->resource->userType?->description,
            'is_active' => (bool) $this->resource->is_active,
            'is_deleted' => $this->resource->trashed(),
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
        ];
    }
}
