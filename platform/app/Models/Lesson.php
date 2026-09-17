<?php

namespace App\Models;

use App\Enums\LessonKind;
use App\Enums\WeekParity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['weekday', 'parity', 'number', 'starts_at', 'ends_at', 'title', 'kind', 'teacher', 'room', 'is_online'])]
class Lesson extends Model
{
    protected function casts(): array
    {
        return [
            'parity' => WeekParity::class,
            'kind' => LessonKind::class,
            'is_online' => 'boolean',
        ];
    }

    /** @return BelongsTo<StudyGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(StudyGroup::class, 'study_group_id');
    }

    public function startTime(): string
    {
        return substr($this->starts_at, 0, 5);
    }

    public function endTime(): string
    {
        return substr($this->ends_at, 0, 5);
    }
}
