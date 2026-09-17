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
            /** Порог базового уровня по навыку, %. */
            'basic_threshold' => $this->basic_threshold,
            /** Минимум вопросов на навык для оценки. */
            'min_questions_per_skill' => $this->min_questions_per_skill,
            'published_at' => $this->published_at,
            'questions_count' => $this->whenCounted('questions'),
            'questions' => AssessmentQuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at,
        ];
    }
}
