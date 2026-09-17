<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['assessment_version_id', 'user_id', 'started_at', 'deadline_at'])]
class AssessmentAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'expired' => 'boolean',
        ];
    }

    /** @return BelongsTo<AssessmentVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AssessmentVersion::class, 'assessment_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AttemptAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    /** @return HasMany<SkillResult, $this> */
    public function skillResults(): HasMany
    {
        return $this->hasMany(SkillResult::class);
    }

    /** @return HasOne<PracticalSubmission, $this> */
    public function practical(): HasOne
    {
        return $this->hasOne(PracticalSubmission::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isSubmitted() && $this->deadline_at !== null && $this->deadline_at->isPast();
    }
}
