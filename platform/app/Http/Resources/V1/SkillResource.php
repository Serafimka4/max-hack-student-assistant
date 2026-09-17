<?php

namespace App\Http\Resources\V1;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Skill */
class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction,
            'title' => $this->title,
            /** null — навык из общего справочника платформы. */
            'organization_id' => $this->organization_id,
        ];
    }
}
