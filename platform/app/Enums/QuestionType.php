<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasLabel
{
    case Single = 'single';
    case Multiple = 'multiple';

    public function getLabel(): string
    {
        return match ($this) {
            self::Single => 'Один ответ',
            self::Multiple => 'Несколько ответов',
        };
    }
}
