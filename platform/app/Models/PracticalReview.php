<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reviewer_id', 'scores', 'comment', 'is_appeal', 'created_at'])]
class PracticalReview extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'is_appeal' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<PracticalSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(PracticalSubmission::class, 'practical_submission_id');
    }
}
