<?php

namespace App\Filament\Resources\InternshipOffers\Concerns;

/** Требования к навыкам хранятся в связке с уровнем, поэтому синхронизируются вне формы. */
trait SyncsRequiredSkills
{
    /** @var list<array<string, mixed>> */
    protected array $requirements = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withoutRequirements(array $data): array
    {
        $this->requirements = array_values($data['requirements'] ?? []);
        unset($data['requirements']);

        return $data;
    }

    protected function syncRequirements(): void
    {
        $pivot = collect($this->requirements)
            ->filter(fn (array $row) => filled($row['skill_id'] ?? null))
            ->mapWithKeys(fn (array $row) => [(int) $row['skill_id'] => ['level' => $row['level']]])
            ->all();

        $this->getRecord()->skills()->sync($pivot);
    }
}
