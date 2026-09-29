<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['internship_offer_id', 'user_id', 'status', 'share_results'])]
class InternshipApplication extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'share_results' => 'boolean',
        ];
    }

    /** @return BelongsTo<InternshipOffer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(InternshipOffer::class, 'internship_offer_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<EmployerInterest, $this> */
    public function interest(): HasOne
    {
        return $this->hasOne(EmployerInterest::class);
    }

    /** @return HasMany<ApplicationStatusChange, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(ApplicationStatusChange::class)->orderBy('created_at')->orderBy('id');
    }
}
