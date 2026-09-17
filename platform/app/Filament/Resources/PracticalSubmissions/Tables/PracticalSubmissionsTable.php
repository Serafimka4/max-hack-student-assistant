<?php

namespace App\Filament\Resources\PracticalSubmissions\Tables;

use App\Enums\PracticalStatus;
use App\Filament\Resources\PracticalSubmissions\PracticalSubmissionResource;
use App\Models\PracticalSubmission;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PracticalSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['attempt.version.assessment']))
            ->columns([
                // Проверяющий не видит имени студента — только номер попытки.
                TextColumn::make('assessment_attempt_id')->label('Попытка')->prefix('№ '),
                TextColumn::make('attempt.version.assessment.title')->label('Тест')->searchable()
                    ->description(fn (PracticalSubmission $record) => 'версия v'.$record->attempt->version->version),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('submitted_at')->label('Отправлено')->since()->sortable(),
                TextColumn::make('reviewed_at')->label('Проверено')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->defaultSort('submitted_at')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(PracticalStatus::class)
                    ->default(null),
            ])
            ->recordUrl(fn (PracticalSubmission $record) => PracticalSubmissionResource::getUrl('review', ['record' => $record]))
            ->recordActions([
                Action::make('review')
                    ->label(fn (PracticalSubmission $record) => $record->status->needsReview() ? 'Проверить' : 'Открыть')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (PracticalSubmission $record) => PracticalSubmissionResource::getUrl('review', ['record' => $record])),
            ]);
    }
}
