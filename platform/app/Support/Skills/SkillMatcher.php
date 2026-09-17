<?php

namespace App\Support\Skills;

use App\Enums\SkillLevel;
use App\Models\InternshipOffer;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Сопоставляет требования предложения с проверенными уровнями студента.
 * Отсутствие проверенного результата — «не оценено», а не низкий уровень.
 */
class SkillMatcher
{
    /**
     * Проверенные уровни пользователя по навыкам.
     * Пока попытки тестов не реализованы, проверенных результатов нет.
     *
     * @return array<int, SkillLevel>
     */
    public function verifiedLevels(User $user): array
    {
        return [];
    }

    /** @return Collection<int, array{skill: Skill, required: SkillLevel, mine: ?SkillLevel, state: 'ok'|'gap'|'unknown'}> */
    public function match(InternshipOffer $offer, User $user): Collection
    {
        $levels = $this->verifiedLevels($user);

        return $offer->skills->map(function (Skill $skill) use ($levels) {
            $required = $skill->pivot->level;
            $mine = $levels[$skill->id] ?? null;

            return [
                'skill' => $skill,
                'required' => $required,
                'mine' => $mine,
                'state' => match (true) {
                    $mine === null => 'unknown',
                    $mine->rank() >= $required->rank() => 'ok',
                    default => 'gap',
                },
            ];
        });
    }
}
