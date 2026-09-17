<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['status', 'changed_by', 'comment', 'created_at'])]
class ApplicationStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['status' => ApplicationStatus::class];
    }
}
