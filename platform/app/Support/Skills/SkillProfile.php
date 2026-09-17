<?php

namespace App\Support\Skills;

use App\Enums\SkillOutcome;
use App\Models\AssessmentAttempt;
use App\Models\SkillResult;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Профиль навыков студента: последний результат по каждому навыку и предварительный грейд по направлению.
 */
class SkillProfile
{
    /** @return Collection<int, SkillResult> последний результат по каждому навыку, ключ — skill_id */
    public function latestResults(User $user): Collection
    {
        return $user->skillResults()
            ->with(['skill', 'attempt.version.assessment'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->keyBy('skill_id');
    }

    /**
     * Предварительный грейд по завершённой попытке.
     *
     * - «Начальный» — базовый уровень подтверждён хотя бы по одному навыку;
     * - «Стажёр» — базовый уровень подтверждён по всем навыкам версии теста;
     * - «Junior — предварительно» выставляется только после проверки практического задания специалистом
     *   (в текущей версии не поддерживается) — поэтому здесь не присваивается.
     */
    public function grade(AssessmentAttempt $attempt): ?string
    {
        $results = $attempt->skillResults;
        $achieved = $results->where('outcome', SkillOutcome::Achieved)->count();

        return match (true) {
            $results->isEmpty() || $achieved === 0 => null,
            $achieved === $results->count() => 'Стажёр',
            default => 'Начальный',
        };
    }
}
