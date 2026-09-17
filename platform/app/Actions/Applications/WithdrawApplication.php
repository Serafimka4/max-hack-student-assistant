<?php

namespace App\Actions\Applications;

use App\Enums\ApplicationStatus;
use App\Exceptions\DomainRuleException;
use App\Models\InternshipApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class WithdrawApplication
{
    public function __invoke(InternshipApplication $application, User $user): InternshipApplication
    {
        if ($application->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        if (! $application->status->canBeWithdrawn()) {
            throw new DomainRuleException('Заявку с решением отозвать нельзя. Обратитесь к координатору.');
        }

        return DB::transaction(function () use ($application, $user) {
            $application->update(['status' => ApplicationStatus::Withdrawn]);
            $application->history()->create(['status' => ApplicationStatus::Withdrawn, 'changed_by' => $user->id, 'created_at' => now()]);

            return $application;
        });
    }
}
