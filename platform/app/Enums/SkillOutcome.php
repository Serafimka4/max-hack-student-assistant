<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Итог диагностики по навыку. «Недостаточно данных» — это не низкая оценка. */
enum SkillOutcome: string implements HasLabel
{
    case Achieved = 'achieved';
    case BelowBasic = 'below_basic';
    case InsufficientData = 'insufficient_data';

    public function getLabel(): string
    {
        return match ($this) {
            self::Achieved => 'Уровень подтверждён',
            self::BelowBasic => 'Базовый уровень не подтверждён',
            self::InsufficientData => 'Недостаточно данных',
        };
    }
}
