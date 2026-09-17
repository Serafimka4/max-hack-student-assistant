<?php

namespace App\Filament\Resources\DocumentTemplates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Карточка шаблона')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Название')->required()->maxLength(255)->columnSpanFull(),
                        TextInput::make('category')->label('Категория')->maxLength(100),
                        TextInput::make('department')->label('Ответственное подразделение')->maxLength(255),
                        Textarea::make('purpose')->label('Назначение и условия использования')->rows(3)->columnSpanFull(),
                        Textarea::make('instructions')->label('Инструкция по заполнению')->rows(5)->columnSpanFull(),
                        Textarea::make('submission')->label('Порядок подачи и приложения')->rows(3)->columnSpanFull(),
                        Toggle::make('is_published')
                            ->label('Опубликован для студентов')
                            ->helperText('Файл загружается во вкладке «Версии файла» после сохранения.'),
                    ]),
            ]);
    }
}
