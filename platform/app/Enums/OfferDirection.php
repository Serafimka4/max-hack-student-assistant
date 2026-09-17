<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OfferDirection: string implements HasLabel
{
    case Development = 'dev';
    case Data = 'data';
    case Design = 'design';

    public function getLabel(): string
    {
        return match ($this) {
            self::Development => 'Разработка',
            self::Data => 'Данные',
            self::Design => 'Дизайн',
        };
    }

    /** Цвет карточки в мини-приложении. */
    public function accent(): string
    {
        return match ($this) {
            self::Development => 'lime',
            self::Data => 'blue',
            self::Design => 'coral',
        };
    }
}
