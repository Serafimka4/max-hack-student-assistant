<?php

namespace App\Filament\Resources\InternshipOffers\Tables;

use App\Enums\OfferDirection;
use App\Models\InternshipOffer;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InternshipOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Название')->searchable()->sortable()
                    ->description(fn (InternshipOffer $record) => $record->company_name),
                TextColumn::make('direction')->label('Направление')->badge(),
                TextColumn::make('places')->label('Мест'),
                TextColumn::make('applications_count')->label('Заявок')->counts('applications'),
                TextColumn::make('apply_until')->label('Приём до')->date('d.m.Y')->placeholder('открыт')
                    ->color(fn (InternshipOffer $record) => $record->isOpen() ? null : 'danger'),
                IconColumn::make('is_published')->label('Опубликовано')->boolean(),
            ])
            ->defaultSort('apply_until')
            ->filters([
                SelectFilter::make('direction')->label('Направление')->options(OfferDirection::class),
                TernaryFilter::make('is_published')->label('Опубликовано'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (InternshipOffer $record) => ! $record->applications()->exists()),
            ]);
    }
}
