<?php

namespace App\Filament\Resources\InternshipApplications;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizationType;
use App\Filament\Resources\InternshipApplications\Pages\ListInternshipApplications;
use App\Filament\Resources\InternshipApplications\Tables\InternshipApplicationsTable;
use App\Models\InternshipApplication;
use App\Models\Organization;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InternshipApplicationResource extends Resource
{
    protected static ?string $model = InternshipApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'заявки на практику';

    protected static string|\UnitEnum|null $navigationGroup = 'Практика';

    protected static ?int $navigationSort = 2;

    /** Заявка принадлежит вузу через предложение, поэтому область видимости задаётся запросом. */
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->whereHas('offer', fn (Builder $query) => $query->whereBelongsTo($tenant, 'organization'))
            ->with(['offer', 'user', 'history']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && $tenant->type === OrganizationType::University;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', ApplicationStatus::Submitted)->count();

        return $count ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return InternshipApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternshipApplications::route('/'),
        ];
    }
}
