<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_question_id', 'selected_keys', 'points_awarded'])]
class AttemptAnswer extends Model
{
    protected function casts(): array
    {
        return ['selected_keys' => 'array'];
    }

    /** @return BelongsTo<AssessmentQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }
}
