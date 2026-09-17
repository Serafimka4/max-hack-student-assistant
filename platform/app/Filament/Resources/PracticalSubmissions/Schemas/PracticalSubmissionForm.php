<?php

namespace App\Filament\Resources\PracticalSubmissions\Schemas;

use App\Models\PracticalSubmission;
use App\Models\Skill;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class PracticalSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        /** @var PracticalSubmission|null $record */
        $record = $schema->getRecord();
        $version = $record?->attempt->version;
        $rubric = array_values($version?->practical_rubric ?? []);
        $skills = Skill::whereIn('id', array_column($rubric, 'skill_id'))->pluck('title', 'id');

        return $schema->components([
            Section::make('Задание')
                ->columnSpanFull()
                ->schema([
                    Text::make(fn () => $version?->practical_task ?? ''),
                ]),

            Section::make('Решение')
                ->description('Имя студента скрыто. Оценивайте только по рубрике.')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('link')->label('Ссылка')->url(fn ($state) => $state)->openUrlInNewTab()->placeholder('—'),
                    TextEntry::make('answer')->label('Описание решения')->placeholder('—')->prose(),
                    TextEntry::make('submitted_at')->label('Отправлено')->dateTime('d.m.Y H:i'),
                    TextEntry::make('appeal_reason')->label('Причина запроса пересмотра')->visible(fn ($state) => filled($state))->color('danger'),
                ]),

            Section::make('Оценка по рубрике')
                ->description($version ? "Пороги версии v{$version->version}: «Прикладной» — от {$version->applied_threshold}%, «Уверенный» — от {$version->confident_threshold}% по критериям навыка." : null)
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    ...array_map(fn (array $criterion, int $i) => TextInput::make("scores.{$i}")
                        ->label($criterion['criterion'])
                        ->helperText(($skills[$criterion['skill_id']] ?? 'Навык').' · максимум '.$criterion['max_points'])
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue((int) $criterion['max_points'])
                        ->required(), $rubric, array_keys($rubric)),
                    Textarea::make('comment')->label('Комментарий для студента')->rows(3)->columnSpanFull()
                        ->helperText('Что получилось и что стоит доработать.'),
                ]),

            Section::make('История проверок')
                ->columnSpanFull()
                ->collapsed()
                ->visible(fn () => $record?->reviews()->exists())
                ->schema([
                    Text::make(fn () => $record?->reviews->map(fn ($review) => $review->created_at->format('d.m.Y H:i')
                        .($review->is_appeal ? ' (пересмотр)' : '')
                        .' · баллы: '.implode(', ', $review->scores)
                        .($review->comment ? ' · «'.$review->comment.'»' : ''))->implode("\n") ?? ''),
                ]),
        ]);
    }
}
