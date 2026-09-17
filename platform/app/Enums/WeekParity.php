<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WeekParity: string implements HasLabel
{
    case Every = 'every';
    case Numerator = 'numerator';
    case Denominator = 'denominator';

    public function getLabel(): string
    {
        return match ($this) {
            self::Every => 'Каждую неделю',
            self::Numerator => 'Числитель',
            self::Denominator => 'Знаменатель',
        };
    }
}
