<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrganizationType: string implements HasLabel
{
    case University = 'university';
    case Employer = 'employer';

    public function getLabel(): string
    {
        return match ($this) {
            self::University => 'Вуз',
            self::Employer => 'Работодатель',
        };
    }
}
