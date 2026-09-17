<?php

namespace App\Actions\Attempts;

use App\Exceptions\DomainRuleException;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Начинает попытку по опубликованной версии теста или возвращает незавершённую.
 * Повторная попытка доступна через retake_after_days после завершения предыдущей.
 */
class StartAttempt
{
    public function __construct(private SubmitAttempt $submit) {}

    public function __invoke(Assessment $assessment, User $user): AssessmentAttempt
    {
        $version = $assessment->publishedVersion ?? throw new DomainRuleException('У теста нет опубликованной версии.');

        return DB::transaction(function () use ($assessment, $version, $user) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $attempts = AssessmentAttempt::query()
                ->where('user_id', $user->id)
                ->whereHas('version', fn ($q) => $q->where('assessment_id', $assessment->id));

            $open = (clone $attempts)->whereNull('submitted_at')->latest('id')->first();
            if ($open && ! $open->isOverdue()) {
                return $open;
            }
            if ($open) {
                ($this->submit)($open);
            }

            $last = (clone $attempts)->whereNotNull('submitted_at')->latest('submitted_at')->first();
            $availableAt = $last?->submitted_at->addDays($assessment->retake_after_days);
            if ($availableAt?->isFuture()) {
                throw new DomainRuleException('Повторная попытка будет доступна '.$availableAt->timezone(config('miniapp.timezone'))->translatedFormat('j F').'.');
            }

            return AssessmentAttempt::create([
                'assessment_version_id' => $version->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'deadline_at' => $assessment->duration_minutes ? now()->addMinutes($assessment->duration_minutes) : null,
            ]);
        });
    }
}
