<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MemberRole: string implements HasLabel
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Coordinator = 'coordinator';
    case Reviewer = 'reviewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Editor => 'Редактор',
            self::Coordinator => 'Координатор',
            self::Reviewer => 'Проверяющий',
        };
    }

    /** Может проверять практические задания по тестам организации. */
    public function canReview(): bool
    {
        return in_array($this, [self::Admin, self::Reviewer], true);
    }

    /** Может создавать и менять тесты и шаблоны организации. */
    public function canManageContent(): bool
    {
        return in_array($this, [self::Admin, self::Editor], true);
    }
}
