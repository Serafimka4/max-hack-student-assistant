<?php

namespace App\Actions\Practical;

use App\Enums\PracticalStatus;
use App\Exceptions\DomainRuleException;
use App\Models\AssessmentAttempt;
use App\Models\PracticalSubmission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/** Отправка решения практического задания после завершения вопросов. До проверки решение можно заменить. */
class SubmitPractical
{
    public function __invoke(AssessmentAttempt $attempt, User $user, ?string $link, ?string $answer): PracticalSubmission
    {
        if ($attempt->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        $version = $attempt->version;
        if (! $attempt->isSubmitted() || ! $version->hasPractical()) {
            throw new DomainRuleException('Практическое задание доступно после завершения вопросов теста.');
        }

        $link = filled($link) ? trim($link) : null;
        $answer = filled($answer) ? trim($answer) : null;
        if ($link === null && $answer === null) {
            throw new DomainRuleException('Добавьте ссылку на решение или опишите его.');
        }

        $submission = $attempt->practical;
        if ($submission && $submission->status !== PracticalStatus::Pending) {
            throw new DomainRuleException('Решение уже проверено. Для пересмотра оценки отправьте запрос.');
        }

        $submission ??= new PracticalSubmission([
            'assessment_attempt_id' => $attempt->id,
            'organization_id' => $version->assessment->organization_id,
        ]);
        $submission->fill([
            'link' => $link,
            'answer' => $answer,
            'status' => PracticalStatus::Pending,
            'submitted_at' => now(),
        ])->save();

        return $submission;
    }
}
