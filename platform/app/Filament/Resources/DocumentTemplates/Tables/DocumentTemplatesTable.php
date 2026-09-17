<?php

namespace App\Filament\Resources\DocumentTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DocumentTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('category')->label('Категория')->badge()->toggleable(),
                TextColumn::make('department')->label('Подразделение')->toggleable(),
                TextColumn::make('currentVersion.version')->label('Версия')->prefix('v')->placeholder('нет файла'),
                IconColumn::make('is_published')->label('Опубликован')->boolean(),
                TextColumn::make('updated_at')->label('Обновлён')->since()->sortable(),
            ])
            ->defaultSort('title')
            ->filters([
                TernaryFilter::make('is_published')->label('Опубликован'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
