<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['title', 'direction', 'description', 'duration_minutes', 'retake_after_days'])]
class Assessment extends Model
{
    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<AssessmentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(AssessmentVersion::class)->orderByDesc('version');
    }

    /** @return HasOne<AssessmentVersion, $this> */
    public function publishedVersion(): HasOne
    {
        return $this->hasOne(AssessmentVersion::class)->ofMany(
            ['version' => 'max'],
            fn ($query) => $query->whereNotNull('published_at'),
        );
    }
}
