<?php

namespace App\Filament\Resources\Tickets;

use App\Enums\OrganizationType;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Organization;
use App\Models\Ticket;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $modelLabel = 'обращение';

    protected static ?string $pluralModelLabel = 'обращения';

    protected static string|\UnitEnum|null $navigationGroup = 'Поддержка студентов';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && $tenant->type === OrganizationType::University;
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();
        $count = $tenant ? Ticket::whereBelongsTo($tenant, 'organization')->whereNot('status', TicketStatus::Resolved)->count() : 0;

        return $count ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
        ];
    }
}
