<?php

namespace App\Support\Skills;

use App\Enums\PracticalStatus;
use App\Enums\SkillLevel;
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
     * Предварительный грейд по завершённой попытке (в рамках направления теста).
     *
     * - «Начальный» — базовый уровень подтверждён хотя бы по одному навыку;
     * - «Стажёр» — базовый уровень подтверждён по всем навыкам версии теста;
     * - «Junior — предварительно» — после проверки практического задания специалистом
     *   прикладной уровень или выше подтверждён по всем навыкам.
     * Уровни middle/senior короткая диагностика не присваивает.
     */
    public function grade(AssessmentAttempt $attempt): ?string
    {
        $results = $attempt->skillResults;
        $achieved = $results->where('outcome', SkillOutcome::Achieved)->count();
        $applied = $results->filter(fn ($r) => $r->level && $r->level->rank() >= SkillLevel::Applied->rank())->count();

        return match (true) {
            $results->isEmpty() || $achieved === 0 => null,
            $applied === $results->count() && $attempt->practical?->status === PracticalStatus::Reviewed => 'Junior — предварительно',
            $achieved === $results->count() => 'Стажёр',
            default => 'Начальный',
        };
    }
}
