<?php

namespace App\Http\Resources\V1;

use App\Models\AssessmentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssessmentVersion */
class AssessmentVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'notes' => $this->notes,
            'is_published' => $this->isPublished(),
            'published_at' => $this->published_at,
            'questions_count' => $this->whenCounted('questions'),
            'questions' => AssessmentQuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
        ];
    }
}
