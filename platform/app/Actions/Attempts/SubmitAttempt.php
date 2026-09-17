<?php

namespace App\Actions\Attempts;

use App\Enums\SkillLevel;
use App\Enums\SkillOutcome;
use App\Models\AssessmentAttempt;
use Illuminate\Support\Facades\DB;

/**
 * Завершает попытку и считает результат на сервере.
 *
 * Правила (версионируются вместе с версией теста):
 * - вопрос засчитывается, только если выбранные варианты полностью совпадают с правильными; частичных баллов нет;
 * - балл по навыку = полученные баллы / максимальные баллы вопросов этого навыка;
 * - меньше min_questions_per_skill вопросов по навыку — «недостаточно данных»;
 * - балл не ниже basic_threshold — «Базовый». Прикладной и уверенный уровни требуют проверенного практического задания,
 *   поэтому закрытые вопросы их не присваивают.
 */
class SubmitAttempt
{
    public function __invoke(AssessmentAttempt $attempt): AssessmentAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $attempt = AssessmentAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($attempt->isSubmitted()) {
                return $attempt;
            }

            $version = $attempt->version()->with('questions')->firstOrFail();
            $answers = $attempt->answers()->get()->keyBy('assessment_question_id');
            $bySkill = [];

            foreach ($version->questions as $question) {
                $answer = $answers->get($question->id);
                $selected = $answer ? $answer->selected_keys : [];
                $correct = $question->correct_keys;
                sort($selected);
                sort($correct);
                $points = $selected === $correct ? $question->points : 0;

                $answer?->update(['points_awarded' => $points]);

                $bySkill[$question->skill_id] ??= ['points' => 0, 'max' => 0, 'count' => 0];
                $bySkill[$question->skill_id]['points'] += $points;
                $bySkill[$question->skill_id]['max'] += $question->points;
                $bySkill[$question->skill_id]['count']++;
            }

            foreach ($bySkill as $skillId => $row) {
                $percent = $row['max'] > 0 ? $row['points'] / $row['max'] * 100 : 0;
                $outcome = match (true) {
                    $row['count'] < $version->min_questions_per_skill => SkillOutcome::InsufficientData,
                    $percent >= $version->basic_threshold => SkillOutcome::Achieved,
                    default => SkillOutcome::BelowBasic,
                };

                $attempt->skillResults()->create([
                    'user_id' => $attempt->user_id,
                    'skill_id' => $skillId,
                    'points' => $row['points'],
                    'max_points' => $row['max'],
                    'questions_count' => $row['count'],
                    'outcome' => $outcome,
                    'level' => $outcome === SkillOutcome::Achieved ? SkillLevel::Basic : null,
                ]);
            }

            $attempt->forceFill([
                'submitted_at' => now(),
                'expired' => $attempt->deadline_at?->isPast() ?? false,
            ])->save();

            return $attempt;
        });
    }
}
