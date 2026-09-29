<?php

namespace App\Filament\Resources\InternshipOffers;

use App\Enums\OrganizationType;
use App\Filament\Resources\InternshipOffers\Pages\CreateInternshipOffer;
use App\Filament\Resources\InternshipOffers\Pages\EditInternshipOffer;
use App\Filament\Resources\InternshipOffers\Pages\ListInternshipOffers;
use App\Filament\Resources\InternshipOffers\Schemas\InternshipOfferForm;
use App\Filament\Resources\InternshipOffers\Tables\InternshipOffersTable;
use App\Models\InternshipOffer;
use App\Models\Organization;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InternshipOfferResource extends Resource
{
    protected static ?string $model = InternshipOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $modelLabel = 'практика';

    protected static ?string $pluralModelLabel = 'практики и стажировки';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Практика';

    protected static ?int $navigationSort = 1;

    /** Каталог практик ведёт вуз. */
    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && $tenant->type === OrganizationType::University;
    }

    public static function form(Schema $schema): Schema
    {
        return InternshipOfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternshipOffersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternshipOffers::route('/'),
            'create' => CreateInternshipOffer::route('/create'),
            'edit' => EditInternshipOffer::route('/{record}/edit'),
        ];
    }
}
