<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'direction', 'title'])]
class Skill extends Model
{
    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Навыки, доступные организации: общий справочник и собственные. */
    public function scopeAvailableTo(Builder $query, Organization $organization): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereNull('organization_id')
            ->orWhere('organization_id', $organization->id));
    }
}
