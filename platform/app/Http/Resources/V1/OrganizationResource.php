<?php

namespace App\Http\Resources\V1;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'slug' => $this->slug,
            /** Роль текущего пользователя, если он участник организации. */
            'role' => $this->whenPivotLoaded('organization_user', fn () => $this->pivot->role),
        ];
    }
}
