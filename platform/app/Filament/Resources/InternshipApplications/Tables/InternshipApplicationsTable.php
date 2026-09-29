<?php

namespace App\Filament\Resources\InternshipApplications\Tables;

use App\Actions\Applications\ChangeApplicationStatus;
use App\Enums\ApplicationStatus;
use App\Exceptions\DomainRuleException;
use App\Models\InternshipApplication;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InternshipApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Студент')->searchable()->sortable(),
                TextColumn::make('offer.title')->label('Практика')->searchable()
                    ->description(fn (InternshipApplication $record) => $record->offer->company_name),
                TextColumn::make('status')->label('Статус')->badge(),
                IconColumn::make('share_results')->label('Результаты')->boolean()
                    ->tooltip(fn (InternshipApplication $record) => $record->share_results
                        ? 'Студент разрешил показать результаты диагностики работодателю'
                        : 'Без результатов диагностики'),
                IconColumn::make('interest')->label('Интерес')->boolean()
                    ->state(fn (InternshipApplication $record) => $record->interest()->exists())
                    ->tooltip('Работодатель отметил интерес к кандидату'),
                TextColumn::make('created_at')->label('Подана')->date('d.m.Y')->sortable(),
                TextColumn::make('updated_at')->label('Обновлена')->since()->sortable(),
            ])
            ->defaultSort('created_at')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(ApplicationStatus::class),
                SelectFilter::make('internship_offer_id')->label('Практика')->relationship('offer', 'title'),
                Filter::make('overdue')
                    ->label('Без ответа дольше 5 дней')
                    ->query(fn ($query) => $query->where('status', ApplicationStatus::Submitted)
                        ->where('created_at', '<=', now()->subDays(5))),
            ])
            ->recordActions([
                self::decision('review', ApplicationStatus::InReview, 'Взять на рассмотрение', Heroicon::OutlinedEye, 'info'),
                self::decision('accept', ApplicationStatus::Accepted, 'Принять', Heroicon::OutlinedCheck, 'success'),
                self::decision('reject', ApplicationStatus::Rejected, 'Отказать', Heroicon::OutlinedXMark, 'danger', required: true),
                ActionGroup::make([
                    Action::make('history')
                        ->label('История')
                        ->icon(Heroicon::OutlinedClock)
                        ->modalSubmitAction(false)
                        ->schema(fn (InternshipApplication $record) => [
                            Text::make($record->history
                                ->map(fn ($change) => $change->created_at->format('d.m.Y H:i').' · '.$change->status->getLabel()
                                    .($change->comment ? ' · «'.$change->comment.'»' : ''))
                                ->implode("\n")),
                        ]),
                ]),
            ]);
    }

    private static function decision(string $name, ApplicationStatus $status, string $label, Heroicon $icon, string $color, bool $required = false): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (InternshipApplication $record) => Auth::user()->can('update', $record)
                && in_array($record->status, [ApplicationStatus::Submitted, ApplicationStatus::InReview], true)
                && $record->status !== $status)
            ->schema([
                Textarea::make('comment')
                    ->label($required ? 'Причина отказа' : 'Комментарий для студента')
                    ->rows(3)
                    ->required($required),
            ])
            ->action(function (InternshipApplication $record, array $data, Action $action) use ($status) {
                try {
                    app(ChangeApplicationStatus::class)($record, Auth::user(), $status, $data['comment'] ?? null);
                    Notification::make()->success()->title('Статус обновлён: '.$status->getLabel())->send();
                } catch (DomainRuleException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();
                }
            });
    }
}
