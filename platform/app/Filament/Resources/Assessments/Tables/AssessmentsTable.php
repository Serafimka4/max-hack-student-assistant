<?php

namespace App\Filament\Resources\Assessments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssessmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable(),
                TextColumn::make('direction')->label('Направление')->badge()->searchable(),
                TextColumn::make('publishedVersion.version')->label('Опубликована')->prefix('v')->placeholder('черновик'),
                TextColumn::make('versions_count')->label('Версий')->counts('versions'),
                TextColumn::make('updated_at')->label('Обновлён')->since()->sortable(),
            ])
            ->defaultSort('title')
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
