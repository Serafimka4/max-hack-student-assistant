<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SkillLevel: string implements HasLabel
{
    case Basic = 'basic';
    case Applied = 'applied';
    case Confident = 'confident';

    public function getLabel(): string
    {
        return match ($this) {
            self::Basic => 'Базовый',
            self::Applied => 'Прикладной',
            self::Confident => 'Уверенный',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Basic => 1,
            self::Applied => 2,
            self::Confident => 3,
        };
    }
}
