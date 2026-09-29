<?php

namespace App\Models;

use App\Enums\OrganizationType;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'name', 'short_name', 'slug'])]
class Organization extends Model implements HasName
{
    use HasFactory;

    protected function casts(): array
    {
        return ['type' => OrganizationType::class];
    }

    public function getFilamentName(): string
    {
        return $this->short_name ?: $this->name;
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasMany<Skill, $this> */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    /** @return HasMany<DocumentTemplate, $this> */
    public function documentTemplates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    /** @return HasMany<Assessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /** @return HasMany<PracticalSubmission, $this> */
    public function practicalSubmissions(): HasMany
    {
        return $this->hasMany(PracticalSubmission::class);
    }

    /** @return HasMany<StudyGroup, $this> */
    public function studyGroups(): HasMany
    {
        return $this->hasMany(StudyGroup::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<FaqItem, $this> */
    public function faqItems(): HasMany
    {
        return $this->hasMany(FaqItem::class);
    }

    /** @return HasMany<InternshipOffer, $this> */
    public function internshipOffers(): HasMany
    {
        return $this->hasMany(InternshipOffer::class);
    }
}
