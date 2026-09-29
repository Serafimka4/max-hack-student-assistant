<?php

namespace App\Actions\Practical;

use App\Enums\PracticalStatus;
use App\Enums\SkillLevel;
use App\Enums\SkillOutcome;
use App\Exceptions\DomainRuleException;
use App\Models\PracticalReview;
use App\Models\PracticalSubmission;
use App\Models\User;
use App\Notifications\PracticalReviewed;
use Illuminate\Support\Facades\DB;

/**
 * Оценка практического задания проверяющим по рубрике версии теста.
 *
 * Уровень по навыку после проверки (пороги версии теста):
 * - практика повышает уровень только там, где базовый подтверждён вопросами;
 * - доля баллов по критериям навыка не ниже applied_threshold — «Прикладной», не ниже confident_threshold — «Уверенный»;
 * - навыки без критериев в рубрике остаются с уровнем по вопросам.
 * Каждая проверка сохраняется в истории; повторная (пересмотр) заменяет итог.
 */
class ReviewPractical
{
    /** @param list<int|string> $scores баллы по критериям рубрики в её порядке */
    public function __invoke(PracticalSubmission $submission, User $reviewer, array $scores, ?string $comment): PracticalReview
    {
        $attempt = $submission->attempt()->with(['version', 'skillResults'])->firstOrFail();
        $version = $attempt->version;
        $rubric = array_values($version->practical_rubric ?? []);

        if ($attempt->user_id === $reviewer->id) {
            throw new DomainRuleException('Нельзя проверять собственное решение.');
        }
        if (count($scores) !== count($rubric)) {
            throw new DomainRuleException('Поставьте баллы по каждому критерию рубрики.');
        }

        $scores = array_map('intval', array_values($scores));
        foreach ($rubric as $i => $criterion) {
            if ($scores[$i] < 0 || $scores[$i] > (int) $criterion['max_points']) {
                throw new DomainRuleException('Критерий «'.$criterion['criterion'].'»: от 0 до '.$criterion['max_points'].' баллов.');
            }
        }

        $review = DB::transaction(function () use ($submission, $reviewer, $scores, $comment, $attempt, $version, $rubric) {
            $bySkill = [];
            foreach ($rubric as $i => $criterion) {
                $skillId = (int) $criterion['skill_id'];
                $bySkill[$skillId] ??= ['points' => 0, 'max' => 0];
                $bySkill[$skillId]['points'] += $scores[$i];
                $bySkill[$skillId]['max'] += (int) $criterion['max_points'];
            }

            foreach ($attempt->skillResults as $result) {
                $practical = $bySkill[$result->skill_id] ?? null;
                $level = $result->outcome === SkillOutcome::Achieved ? SkillLevel::Basic : null;

                if ($practical && $level && $practical['max'] > 0) {
                    $percent = $practical['points'] / $practical['max'] * 100;
                    $level = match (true) {
                        $percent >= $version->confident_threshold => SkillLevel::Confident,
                        $percent >= $version->applied_threshold => SkillLevel::Applied,
                        default => SkillLevel::Basic,
                    };
                }

                $result->update([
                    'practical_points' => $practical['points'] ?? null,
                    'practical_max_points' => $practical['max'] ?? null,
                    'level' => $level,
                ]);
            }

            $review = $submission->reviews()->create([
                'reviewer_id' => $reviewer->id,
                'scores' => $scores,
                'comment' => filled($comment) ? trim($comment) : null,
                'is_appeal' => $submission->status === PracticalStatus::Appeal,
                'created_at' => now(),
            ]);

            $submission->forceFill(['status' => PracticalStatus::Reviewed, 'reviewed_at' => now()])->save();

            return $review;
        });

        // Уведомление после фиксации изменений, чтобы очередь не увидела незавершённую транзакцию.
        $attempt->user->notify(new PracticalReviewed($attempt));

        return $review;
    }
}
