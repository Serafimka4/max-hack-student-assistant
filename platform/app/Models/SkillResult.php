<?php

namespace App\Models;

use App\Enums\SkillLevel;
use App\Enums\SkillOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'skill_id', 'points', 'max_points', 'questions_count', 'level', 'outcome', 'practical_points', 'practical_max_points'])]
class SkillResult extends Model
{
    protected function casts(): array
    {
        return [
            'level' => SkillLevel::class,
            'outcome' => SkillOutcome::class,
        ];
    }

    /** @return BelongsTo<Skill, $this> */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /** @return BelongsTo<AssessmentAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    public function practicalPercent(): ?int
    {
        return $this->practical_max_points ? (int) round($this->practical_points / $this->practical_max_points * 100) : null;
    }

    public function percent(): int
    {
        return $this->max_points > 0 ? (int) round($this->points / $this->max_points * 100) : 0;
    }
}
