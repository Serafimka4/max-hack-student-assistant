<?php

namespace App\Filament\Resources\PracticalSubmissions\Pages;

use App\Filament\Resources\PracticalSubmissions\PracticalSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPracticalSubmissions extends ListRecords
{
    protected static string $resource = PracticalSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
