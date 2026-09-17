<?php

namespace App\Filament\Resources\Assessments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Тест')
                    ->description('Вопросы добавляются в версии теста после сохранения. Опубликованная версия не меняется.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Название')->required()->maxLength(255),
                        TextInput::make('direction')->label('Профессиональное направление')->required()->maxLength(255)
                            ->placeholder('Frontend-разработка'),
                        Textarea::make('description')->label('Описание для студента')->rows(3)->columnSpanFull(),
                        TextInput::make('duration_minutes')->label('Длительность, мин')->numeric()->minValue(1)->maxValue(600),
                        TextInput::make('retake_after_days')->label('Повторная попытка через, дней')->numeric()
                            ->minValue(0)->maxValue(365)->default(14)->required(),
                    ]),
            ]);
    }
}
