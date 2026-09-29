<?php

namespace App\Filament\Pages;

use App\Actions\Employers\MarkCandidateInterest;
use App\Enums\OrganizationType;
use App\Enums\PracticalStatus;
use App\Enums\SkillLevel;
use App\Exceptions\DomainRuleException;
use App\Models\AssessmentAttempt;
use App\Models\InternshipApplication;
use App\Models\Organization;
use App\Models\Skill;
use App\Support\Skills\SkillMatcher;
use App\Support\Skills\SkillProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Отклики на предложения работодателя. Видны только те результаты диагностики,
 * которые студент разрешил показать при подаче заявки.
 */
class Candidates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Кандидаты';

    protected static ?string $title = 'Кандидаты на практику';

    protected static string|\UnitEnum|null $navigationGroup = 'Подбор';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.candidates';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization
            && $tenant->type === OrganizationType::Employer
            && Auth::user()?->roleIn($tenant) !== null;
    }

    private function employer(): Organization
    {
        /** @var Organization */
        return Filament::getTenant();
    }

    public function table(Table $table): Table
    {
        $employer = $this->employer();
        $profile = app(SkillProfile::class);

        return $table
            ->query(fn () => InternshipApplication::query()
                ->where('share_results', true)
                ->whereHas('offer', fn (Builder $query) => $query->where('employer_id', $employer->id))
                ->with(['user.skillResults.skill', 'user.attempts.version.assessment', 'user.attempts.practical', 'offer.skills', 'interest']))
            ->emptyStateHeading('Откликов пока нет')
            ->emptyStateDescription('Здесь появятся студенты, которые подали заявку и разрешили показать результаты диагностики.')
            ->columns([
                TextColumn::make('user.name')->label('Кандидат')->searchable()->sortable(),
                TextColumn::make('offer.title')->label('Предложение')->searchable(),
                TextColumn::make('grade')
                    ->label('Предварительный грейд')
                    ->badge()
                    ->state(fn (InternshipApplication $record) => $this->grade($record, $profile) ?? 'Не оценено')
                    ->color(fn (string $state) => $state === 'Не оценено' ? 'gray' : 'success'),
                TextColumn::make('skills')
                    ->label('Подтверждено по требованиям')
                    ->state(function (InternshipApplication $record) {
                        $match = app(SkillMatcher::class)->match($record->offer, $record->user);

                        return $match->where('state', 'ok')->count().' из '.$match->count();
                    }),
                TextColumn::make('checked_at')
                    ->label('Проверено')
                    ->state(fn (InternshipApplication $record) => $this->lastAttempt($record)?->submitted_at?->format('d.m.Y') ?? '—'),
                TextColumn::make('status')->label('Заявка')->badge(),
                IconColumn::make('interest')->label('Интерес')->boolean()
                    ->state(fn (InternshipApplication $record) => $record->interest !== null),
            ])
            ->filters([
                SelectFilter::make('offer')->label('Предложение')->relationship('offer', 'title'),
                Filter::make('skill')
                    ->label('Навык и уровень')
                    ->schema([
                        Select::make('skill_id')->label('Навык')->searchable()
                            ->options(fn () => Skill::whereHas('internshipOffers', fn (Builder $q) => $q->where('employer_id', $this->employer()->id))
                                ->orderBy('title')->pluck('title', 'id')),
                        Select::make('level')->label('Не ниже уровня')->options(SkillLevel::class)->default(SkillLevel::Basic->value),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (blank($data['skill_id'] ?? null)) {
                            return;
                        }

                        $required = SkillLevel::tryFrom($data['level'] ?? '') ?? SkillLevel::Basic;
                        $levels = array_filter(SkillLevel::cases(), fn (SkillLevel $level) => $level->rank() >= $required->rank());

                        $query->whereHas('user.skillResults', fn (Builder $results) => $results
                            ->where('skill_id', $data['skill_id'])
                            ->whereIn('level', array_column($levels, 'value')));
                    }),
                TernaryFilter::make('assessed')
                    ->label('С результатами диагностики')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user.skillResults'),
                        false: fn (Builder $query) => $query->whereDoesntHave('user.skillResults'),
                        blank: fn (Builder $query) => $query,
                    ),
                TernaryFilter::make('practical')
                    ->label('Практика проверена')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('user.attempts.practical', fn (Builder $q) => $q->where('status', PracticalStatus::Reviewed)),
                        false: fn (Builder $query) => $query->whereDoesntHave('user.attempts.practical', fn (Builder $q) => $q->where('status', PracticalStatus::Reviewed)),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                Action::make('card')
                    ->label('Карточка')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->slideOver()
                    ->modalHeading(fn (InternshipApplication $record) => $record->user->name)
                    ->modalSubmitAction(false)
                    ->schema(fn (InternshipApplication $record) => [Text::make($this->candidateCard($record))]),
                Action::make('interest')
                    ->label(fn (InternshipApplication $record) => $record->interest ? 'Интерес отмечен' : 'Заинтересован')
                    ->icon(Heroicon::OutlinedHandThumbUp)
                    ->color('success')
                    ->visible(fn (InternshipApplication $record) => Auth::user()->can('markInterest', [$record, $this->employer()]))
                    ->schema([
                        Textarea::make('note')->label('Что передать координатору')->rows(3)
                            ->placeholder('Например: готовы пригласить на собеседование на следующей неделе.'),
                    ])
                    ->action(function (InternshipApplication $record, array $data) {
                        try {
                            app(MarkCandidateInterest::class)($record, $this->employer(), Auth::user(), $data['note'] ?? null);
                            Notification::make()->success()
                                ->title('Интерес отмечен')
                                ->body('Следующий шаг сделает координатор вуза.')
                                ->send();
                        } catch (DomainRuleException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }

    private function lastAttempt(InternshipApplication $record): ?AssessmentAttempt
    {
        return $record->user->attempts->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first();
    }

    private function grade(InternshipApplication $record, SkillProfile $profile): ?string
    {
        $attempt = $this->lastAttempt($record);

        return $attempt ? $profile->grade($attempt) : null;
    }

    /** Основания оценки: что проверено, что требуется и что стоит уточнить на собеседовании. */
    public function candidateCard(InternshipApplication $record): string
    {
        $profile = app(SkillProfile::class);
        $attempt = $this->lastAttempt($record);
        $match = app(SkillMatcher::class)->match($record->offer, $record->user);

        $lines = ["Предложение: {$record->offer->title}", 'Заявка: '.$record->status->getLabel()];

        if ($attempt) {
            $lines[] = 'Диагностика: '.$attempt->version->assessment->title
                .' (версия v'.$attempt->version->version.', '.$attempt->submitted_at->format('d.m.Y').')';
            $lines[] = 'Предварительный грейд: '.($profile->grade($attempt) ?? 'не определён');
            $practical = $attempt->practical;
            $lines[] = 'Практическое задание: '.($practical ? mb_strtolower($practical->status->getLabel()) : 'не отправлено');
        } else {
            $lines[] = 'Диагностика не пройдена — навыки не оценены.';
        }

        $lines[] = '';
        $lines[] = 'Требования предложения:';
        foreach ($match as $row) {
            $result = $row['result'];
            $lines[] = '• '.$row['skill']->title.' — нужно '.mb_strtolower($row['required']->getLabel()).'; '
                .match ($row['state']) {
                    'ok' => 'подтверждено ('.mb_strtolower($row['mine']->getLabel()).', вопросы '.$result->percent().'%'
                        .($result->practicalPercent() !== null ? ', практика '.$result->practicalPercent().'%' : '').')',
                    'gap' => $row['mine']
                        ? 'подтверждён только '.mb_strtolower($row['mine']->getLabel()).' — уточнить на собеседовании'
                        : 'базовый уровень не подтверждён ('.$result->percent().'%)',
                    default => 'не оценено',
                };
        }

        $lines[] = '';
        $lines[] = 'Оценка предварительная: короткая диагностика без наблюдателя. Решение принимает работодатель, приглашение оформляет координатор вуза.';

        return implode("\n", $lines);
    }
}
