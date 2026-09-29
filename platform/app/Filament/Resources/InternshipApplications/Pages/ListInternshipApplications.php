<?php

namespace App\Filament\Resources\InternshipApplications\Pages;

use App\Filament\Resources\InternshipApplications\InternshipApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListInternshipApplications extends ListRecords
{
    protected static string $resource = InternshipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
