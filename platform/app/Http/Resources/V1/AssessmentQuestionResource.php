<?php

namespace App\Http\Resources\V1;

use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssessmentQuestion */
class AssessmentQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'skill_id' => $this->skill_id,
            'type' => $this->type,
            'prompt' => $this->prompt,
            'code' => $this->code,
            'options' => $this->options,
            'points' => $this->points,
            /** Только для редакторов организации. */
            'correct_keys' => $this->when(
                (bool) $request->user()?->can('update', $this->version->assessment),
                fn () => $this->correct_keys,
            ),
        ];
    }
}
