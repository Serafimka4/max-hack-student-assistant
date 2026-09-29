<?php

namespace App\Policies;

use App\Models\InternshipApplication;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class InternshipApplicationPolicy
{
    use ResolvesOrganization;

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && (bool) $user->roleIn($organization)?->canCoordinate();
    }

    /** Вуз видит свои заявки; студент — свою. */
    public function view(User $user, InternshipApplication $application): bool
    {
        return $user->id === $application->user_id
            || (bool) $user->roleIn($application->offer->organization)?->canCoordinate();
    }

    /** Решение по заявке принимает вуз. */
    public function update(User $user, InternshipApplication $application): bool
    {
        return (bool) $user->roleIn($application->offer->organization)?->canCoordinate();
    }

    /**
     * Работодатель видит отклик на своё предложение, только если студент разрешил показать результаты.
     */
    public function viewAsEmployer(User $user, InternshipApplication $application, ?Organization $employer = null): bool
    {
        $employer = $this->organization($employer);

        return $employer !== null
            && $application->offer->employer_id === $employer->id
            && $application->share_results
            && $user->roleIn($employer) !== null;
    }

    public function markInterest(User $user, InternshipApplication $application, ?Organization $employer = null): bool
    {
        $employer = $this->organization($employer);

        return $this->viewAsEmployer($user, $application, $employer)
            && (bool) $user->roleIn($employer)?->canCoordinate();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, InternshipApplication $application): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
