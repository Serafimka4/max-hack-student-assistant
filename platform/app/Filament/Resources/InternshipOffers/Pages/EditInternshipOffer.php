<?php

namespace App\Filament\Resources\InternshipOffers\Pages;

use App\Filament\Resources\InternshipOffers\Concerns\SyncsRequiredSkills;
use App\Filament\Resources\InternshipOffers\InternshipOfferResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInternshipOffer extends EditRecord
{
    use SyncsRequiredSkills;

    protected static string $resource = InternshipOfferResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'requirements' => $this->getRecord()->skills
            ->map(fn ($skill) => ['skill_id' => $skill->id, 'level' => $skill->pivot->level->value])
            ->all()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->withoutRequirements($data);
    }

    protected function afterSave(): void
    {
        $this->syncRequirements();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn () => ! $this->getRecord()->applications()->exists()),
        ];
    }
}
