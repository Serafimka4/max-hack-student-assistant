<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LessonKind: string implements HasLabel
{
    case Lecture = 'lecture';
    case Practice = 'practice';
    case Lab = 'lab';

    public function getLabel(): string
    {
        return match ($this) {
            self::Lecture => 'Лекция',
            self::Practice => 'Практика',
            self::Lab => 'Лаба',
        };
    }
}
