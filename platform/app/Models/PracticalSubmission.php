<?php

namespace App\Models;

use App\Enums\PracticalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['assessment_attempt_id', 'organization_id', 'link', 'answer', 'status', 'appeal_reason', 'submitted_at'])]
class PracticalSubmission extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PracticalStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AssessmentAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<PracticalReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(PracticalReview::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** @return HasOne<PracticalReview, $this> */
    public function latestReview(): HasOne
    {
        return $this->hasOne(PracticalReview::class)->latestOfMany();
    }
}
