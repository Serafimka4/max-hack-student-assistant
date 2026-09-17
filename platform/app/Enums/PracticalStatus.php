<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PracticalStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Appeal = 'appeal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает проверки',
            self::Reviewed => 'Проверено',
            self::Appeal => 'Запрошен пересмотр',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Reviewed => 'success',
            self::Appeal => 'danger',
        };
    }

    public function needsReview(): bool
    {
        return $this !== self::Reviewed;
    }
}
