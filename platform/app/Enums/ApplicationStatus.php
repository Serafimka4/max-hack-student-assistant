<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ApplicationStatus: string implements HasLabel
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Подана',
            self::InReview => 'На рассмотрении',
            self::Accepted => 'Принят',
            self::Rejected => 'Отказ',
            self::Withdrawn => 'Отозвана',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Withdrawn;
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this, [self::Submitted, self::InReview], true);
    }
}
