<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasLabel
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новое',
            self::InProgress => 'В работе',
            self::Resolved => 'Решено',
        };
    }
}
