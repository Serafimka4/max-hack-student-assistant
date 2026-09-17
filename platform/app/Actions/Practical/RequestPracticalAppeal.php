<?php

namespace App\Actions\Practical;

use App\Enums\PracticalStatus;
use App\Exceptions\DomainRuleException;
use App\Models\PracticalSubmission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/** Запрос пересмотра оценки практического задания. */
class RequestPracticalAppeal
{
    public function __invoke(PracticalSubmission $submission, User $user, string $reason): PracticalSubmission
    {
        if ($submission->attempt->user_id !== $user->id) {
            throw new AuthorizationException;
        }
        if ($submission->status !== PracticalStatus::Reviewed) {
            throw new DomainRuleException('Пересмотр можно запросить только после проверки.');
        }
        if ($submission->reviews()->where('is_appeal', true)->exists()) {
            throw new DomainRuleException('Пересмотр по этой попытке уже проводился.');
        }

        $submission->update(['status' => PracticalStatus::Appeal, 'appeal_reason' => trim($reason)]);

        return $submission;
    }
}
