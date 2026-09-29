<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Actions\Tickets\HandleTicket;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'handler']))
            ->columns([
                TextColumn::make('title')->label('Обращение')->searchable()->wrap()
                    ->description(fn (Ticket $record) => $record->context),
                TextColumn::make('category')->label('Тип')->badge(),
                TextColumn::make('status')->label('Статус')->badge()
                    ->color(fn (TicketStatus $state) => match ($state) {
                        TicketStatus::New => 'danger',
                        TicketStatus::InProgress => 'info',
                        TicketStatus::Resolved => 'success',
                    }),
                TextColumn::make('user.name')->label('Студент')->searchable(),
                TextColumn::make('assignee')->label('Ответственный'),
                TextColumn::make('due_at')->label('Срок ответа')->date('d.m.Y')
                    ->color(fn (Ticket $record) => $record->status !== TicketStatus::Resolved && $record->due_at?->isPast() ? 'danger' : null),
                IconColumn::make('confirmed_at')->label('Подтверждено')->boolean()
                    ->tooltip('Студент подтвердил, что проблема решена'),
            ])
            ->defaultSort('created_at')
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(TicketStatus::class),
                SelectFilter::make('category')->label('Тип')->options(TicketCategory::class),
                Filter::make('overdue')->label('Просрочен срок ответа')
                    ->query(fn ($query) => $query->whereNot('status', TicketStatus::Resolved)->where('due_at', '<', now())),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Текст')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->modalSubmitAction(false)
                    ->schema([
                        TextEntry::make('context')->label('Контекст'),
                        TextEntry::make('body')->label('Сообщение студента')->prose(),
                        TextEntry::make('resolution')->label('Решение')->prose()->placeholder('—'),
                    ]),
                Action::make('take')
                    ->label('В работу')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('info')
                    ->visible(fn (Ticket $record) => Auth::user()->can('update', $record) && $record->status === TicketStatus::New)
                    ->schema([
                        TextInput::make('assignee')->label('Ответственный')->maxLength(255)
                            ->helperText('Оставьте пустым, чтобы сохранить текущего.'),
                    ])
                    ->action(fn (Ticket $record, array $data) => self::run(
                        fn () => app(HandleTicket::class)->takeInProgress($record, Auth::user(), $data['assignee'] ?? null),
                        'Обращение в работе',
                    )),
                Action::make('resolve')
                    ->label('Решено')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Ticket $record) => Auth::user()->can('update', $record) && $record->status !== TicketStatus::Resolved)
                    ->schema([
                        Textarea::make('resolution')->label('Что сделано')->rows(3)->required()
                            ->helperText('Текст увидит студент и сможет подтвердить решение.'),
                    ])
                    ->action(fn (Ticket $record, array $data) => self::run(
                        fn () => app(HandleTicket::class)->resolve($record, Auth::user(), $data['resolution']),
                        'Решение зафиксировано',
                    )),
                ActionGroup::make([
                    Action::make('reopen')
                        ->label('Открыть повторно')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->visible(fn (Ticket $record) => Auth::user()->can('update', $record) && $record->status === TicketStatus::Resolved)
                        ->schema([
                            Textarea::make('reason')->label('Почему проблема осталась')->rows(2)->required(),
                        ])
                        ->action(fn (Ticket $record, array $data) => self::run(
                            fn () => app(HandleTicket::class)->reopen($record, Auth::user(), $data['reason']),
                            'Обращение открыто повторно',
                        )),
                ]),
            ]);
    }

    private static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (DomainRuleException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }
}
