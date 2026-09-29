<?php

namespace App\Filament\Resources\FaqItems\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Вопрос и ответ')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('question')->label('Вопрос')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('answer')->label('Ответ')->rows(5)->required()->columnSpanFull()
                        ->helperText('Проверенный ответ: студент видит его вместо обращения в деканат.'),
                    TextInput::make('category')->label('Категория')->required()->maxLength(100)
                        ->placeholder('Справки, Практика, Учёба, Контакты'),
                    TextInput::make('owner')->label('Ответственный за актуальность')->maxLength(255),
                    TextInput::make('position')->label('Порядок')->numeric()->default(0)->required(),
                    Toggle::make('is_published')->label('Показывать студентам')->default(true),
                ]),
        ]);
    }
}
