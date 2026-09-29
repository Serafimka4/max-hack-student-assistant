<?php

namespace App\Filament\Resources\InternshipOffers\Pages;

use App\Filament\Resources\InternshipOffers\InternshipOfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInternshipOffers extends ListRecords
{
    protected static string $resource = InternshipOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
