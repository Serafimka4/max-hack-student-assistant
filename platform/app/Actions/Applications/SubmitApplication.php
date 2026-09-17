<?php

namespace App\Actions\Applications;

use App\Enums\ApplicationStatus;
use App\Exceptions\DomainRuleException;
use App\Models\InternshipApplication;
use App\Models\InternshipOffer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Подаёт заявку на практику. Повторный вызов не создаёт дубликат:
 * активная заявка возвращается как есть, отозванная подаётся заново.
 */
class SubmitApplication
{
    public function __invoke(InternshipOffer $offer, User $user, bool $shareResults): InternshipApplication
    {
        if (! $offer->isOpen()) {
            throw new DomainRuleException('Приём заявок на это предложение закрыт.');
        }

        return DB::transaction(function () use ($offer, $user, $shareResults) {
            $application = InternshipApplication::query()
                ->where(['internship_offer_id' => $offer->id, 'user_id' => $user->id])
                ->lockForUpdate()
                ->first();

            if ($application?->status->isActive()) {
                return $application;
            }

            $application ??= new InternshipApplication(['internship_offer_id' => $offer->id, 'user_id' => $user->id]);
            $application->fill(['status' => ApplicationStatus::Submitted, 'share_results' => $shareResults])->save();
            $application->history()->create(['status' => ApplicationStatus::Submitted, 'changed_by' => $user->id, 'created_at' => now()]);

            return $application;
        });
    }
}
