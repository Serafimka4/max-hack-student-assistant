<?php

namespace App\Actions\Applications;

use App\Enums\ApplicationStatus;
use App\Exceptions\DomainRuleException;
use App\Models\ApplicationStatusChange;
use App\Models\InternshipApplication;
use App\Models\User;
use App\Notifications\ApplicationDecided;
use Illuminate\Support\Facades\DB;

/**
 * Решение по заявке со стороны вуза. Переходы заданы явно:
 * Подана → На рассмотрении → Принят / Отказ. Отзыв студентом и решение — завершающие состояния.
 */
class ChangeApplicationStatus
{
    private const ALLOWED = [
        ApplicationStatus::Submitted->value => [ApplicationStatus::InReview, ApplicationStatus::Accepted, ApplicationStatus::Rejected],
        ApplicationStatus::InReview->value => [ApplicationStatus::Accepted, ApplicationStatus::Rejected],
    ];

    public function __invoke(InternshipApplication $application, User $staff, ApplicationStatus $status, ?string $comment = null): ApplicationStatusChange
    {
        $allowed = self::ALLOWED[$application->status->value] ?? [];

        if (! in_array($status, $allowed, true)) {
            throw new DomainRuleException("Из состояния «{$application->status->getLabel()}» переход в «{$status->getLabel()}» не предусмотрен.");
        }

        if ($status === ApplicationStatus::Rejected && blank($comment)) {
            throw new DomainRuleException('Укажите причину отказа — она будет видна студенту.');
        }

        $change = DB::transaction(function () use ($application, $staff, $status, $comment) {
            $application->update(['status' => $status]);

            return $application->history()->create([
                'status' => $status,
                'changed_by' => $staff->id,
                'comment' => filled($comment) ? trim($comment) : null,
                'created_at' => now(),
            ]);
        });

        $application->user->notify(new ApplicationDecided($application, $status, $change->comment));

        return $change;
    }
}
