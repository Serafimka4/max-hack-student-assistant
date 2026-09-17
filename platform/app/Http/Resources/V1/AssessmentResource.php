<?php

namespace App\Http\Resources\V1;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assessment */
class AssessmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'title' => $this->title,
            'direction' => $this->direction,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'retake_after_days' => $this->retake_after_days,
            'published_version' => AssessmentVersionResource::make($this->whenLoaded('publishedVersion')),
            'updated_at' => $this->updated_at,
        ];
    }
}
