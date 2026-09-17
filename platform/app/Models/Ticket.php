<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Обращение. Конфиденциальное, не анонимное: видят автор, ответственный и координатор. */
#[Fillable(['organization_id', 'user_id', 'category', 'title', 'body', 'context', 'assignee', 'status', 'resolution', 'due_at'])]
class Ticket extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
