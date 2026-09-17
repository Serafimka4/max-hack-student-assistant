<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketCategory: string implements HasLabel
{
    case Schedule = 'schedule';
    case Faq = 'faq';
    case Document = 'document';
    case Internship = 'internship';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Schedule => 'Ошибка в расписании',
            self::Faq => 'Ответ не помог',
            self::Document => 'Проблема с документом',
            self::Internship => 'Нужна помощь с практикой',
            self::Other => 'Вопрос',
        };
    }

    public function defaultAssignee(): string
    {
        return match ($this) {
            self::Schedule => 'Учебный отдел',
            self::Faq, self::Other => 'Деканат',
            self::Document => 'Ответственное подразделение',
            self::Internship => 'Центр карьеры',
        };
    }
}
