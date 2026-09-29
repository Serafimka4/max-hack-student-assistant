<?php

namespace App\Actions\Employers;

use App\Exceptions\DomainRuleException;
use App\Models\EmployerInterest;
use App\Models\InternshipApplication;
use App\Models\Organization;
use App\Models\User;

/**
 * Работодатель отмечает интерес к кандидату. Дальнейший шаг — за координатором вуза:
 * приглашение инициирует человек, автоматического решения по тесту нет.
 */
class MarkCandidateInterest
{
    public function __invoke(InternshipApplication $application, Organization $employer, User $author, ?string $note): EmployerInterest
    {
        if ($application->offer->employer_id !== $employer->id) {
            throw new DomainRuleException('Заявка относится к предложению другой организации.');
        }

        if (! $application->share_results) {
            throw new DomainRuleException('Студент не разрешил показывать результаты по этой заявке.');
        }

        return EmployerInterest::updateOrCreate(
            ['internship_application_id' => $application->id, 'organization_id' => $employer->id],
            ['created_by' => $author->id, 'note' => filled($note) ? trim($note) : null],
        );
    }
}
