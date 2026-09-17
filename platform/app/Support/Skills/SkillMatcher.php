<?php

namespace App\Support\Skills;

use App\Enums\SkillLevel;
use App\Enums\SkillOutcome;
use App\Models\InternshipOffer;
use App\Models\Skill;
use App\Models\SkillResult;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Сопоставляет требования предложения с результатами диагностики студента.
 * Нет результата или недостаточно данных — «не оценено», а не низкий уровень.
 */
class SkillMatcher
{
    public function __construct(private SkillProfile $profile) {}

    /** @return Collection<int, array{skill: Skill, required: SkillLevel, mine: ?SkillLevel, result: ?SkillResult, state: 'ok'|'gap'|'unknown'}> */
    public function match(InternshipOffer $offer, User $user, ?Collection $results = null): Collection
    {
        $results ??= $this->profile->latestResults($user);

        return $offer->skills->map(function (Skill $skill) use ($results) {
            $required = $skill->pivot->level;
            /** @var SkillResult|null $result */
            $result = $results->get($skill->id);
            $mine = $result?->level;

            return [
                'skill' => $skill,
                'required' => $required,
                'mine' => $mine,
                'result' => $result,
                'state' => match (true) {
                    $result === null, $result->outcome === SkillOutcome::InsufficientData => 'unknown',
                    $mine !== null && $mine->rank() >= $required->rank() => 'ok',
                    default => 'gap',
                },
            ];
        });
    }
}
