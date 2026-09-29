<?php

namespace App\Filament\Resources\InternshipOffers\Pages;

use App\Filament\Resources\InternshipOffers\Concerns\SyncsRequiredSkills;
use App\Filament\Resources\InternshipOffers\InternshipOfferResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateInternshipOffer extends CreateRecord
{
    use SyncsRequiredSkills;

    protected static string $resource = InternshipOfferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Организация задаётся явно: не зависим от порядка выполнения middleware панели.
        $data['organization_id'] ??= Filament::getTenant()?->getKey();

        return $this->withoutRequirements($data);
    }

    protected function afterCreate(): void
    {
        $this->syncRequirements();
    }
}
