<?php

namespace App\Models;

use App\Enums\MemberRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'max_user_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Organization, $this> */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasOne<Student, $this> */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** @return HasMany<InternshipApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(InternshipApplication::class);
    }

    /** @return HasMany<AssessmentAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    /** @return HasMany<SkillResult, $this> */
    public function skillResults(): HasMany
    {
        return $this->hasMany(SkillResult::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** Канал MAX: сообщения уходят на идентификатор пользователя в мессенджере. */
    public function routeNotificationForMax(): ?int
    {
        return $this->max_user_id;
    }

    public function roleIn(Organization $organization): ?MemberRole
    {
        if ($this->is_platform_admin) {
            return MemberRole::Admin;
        }

        return $this->organizations()->whereKey($organization->id)->first()?->pivot->role;
    }

    public function canManageContentOf(Organization $organization): bool
    {
        return (bool) $this->roleIn($organization)?->canManageContent();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_platform_admin || $this->organizations()->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->is_platform_admin ? Organization::orderBy('name')->get() : $this->organizations;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Organization && $this->roleIn($tenant) !== null;
    }
}
