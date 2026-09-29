<?php

namespace App\Filament\Resources\FaqItems\Tables;

use App\Models\FaqItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')->label('#')->sortable(),
                TextColumn::make('question')->label('Вопрос')->searchable()->wrap(),
                TextColumn::make('category')->label('Категория')->badge()->searchable(),
                TextColumn::make('owner')->label('Ответственный')->toggleable(),
                // Частые «не помогло» — сигнал переписать ответ или упростить процедуру.
                TextColumn::make('helpful')->label('Помогло / всего')
                    ->state(fn (FaqItem $record) => $record->feedback()->where('helpful', true)->count().' / '.$record->feedback()->count()),
                IconColumn::make('is_published')->label('Опубликован')->boolean(),
                TextColumn::make('updated_at')->label('Обновлён')->since()->sortable(),
            ])
            ->defaultSort('position')
            ->filters([
                SelectFilter::make('category')->label('Категория')
                    ->options(fn () => FaqItem::query()->distinct()->orderBy('category')->pluck('category', 'category')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
