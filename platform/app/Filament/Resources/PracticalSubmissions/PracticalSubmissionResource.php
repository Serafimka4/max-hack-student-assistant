<?php

namespace App\Filament\Resources\PracticalSubmissions;

use App\Enums\PracticalStatus;
use App\Filament\Resources\PracticalSubmissions\Pages\ListPracticalSubmissions;
use App\Filament\Resources\PracticalSubmissions\Pages\ReviewPracticalSubmission;
use App\Filament\Resources\PracticalSubmissions\Schemas\PracticalSubmissionForm;
use App\Filament\Resources\PracticalSubmissions\Tables\PracticalSubmissionsTable;
use App\Models\PracticalSubmission;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PracticalSubmissionResource extends Resource
{
    protected static ?string $model = PracticalSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'практическое задание';

    protected static ?string $pluralModelLabel = 'проверка практики';

    protected static string|\UnitEnum|null $navigationGroup = 'Диагностика навыков';

    protected static ?int $navigationSort = 20;

    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();
        $count = $tenant ? PracticalSubmission::whereBelongsTo($tenant, 'organization')
            ->whereIn('status', [PracticalStatus::Pending, PracticalStatus::Appeal])->count() : 0;

        return $count ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return PracticalSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PracticalSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPracticalSubmissions::route('/'),
            'review' => ReviewPracticalSubmission::route('/{record}/review'),
        ];
    }
}
