<?php

namespace App\Models;

use App\Enums\OfferDirection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'employer_id', 'company_name', 'title', 'direction', 'format', 'starts_on', 'ends_on', 'apply_until', 'places', 'tasks', 'contact', 'is_published'])]
class InternshipOffer extends Model
{
    protected function casts(): array
    {
        return [
            'direction' => OfferDirection::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'apply_until' => 'date',
            'tasks' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'employer_id');
    }

    /** @return BelongsToMany<Skill, $this> */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->using(OfferSkill::class)->withPivot('level');
    }

    /** @return HasMany<InternshipApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function isOpen(): bool
    {
        return $this->is_published && ($this->apply_until === null || ! $this->apply_until->endOfDay()->isPast());
    }
}
