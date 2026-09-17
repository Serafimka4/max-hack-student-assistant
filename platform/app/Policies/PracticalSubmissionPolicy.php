<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\PracticalSubmission;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class PracticalSubmissionPolicy
{
    use ResolvesOrganization;

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && (bool) $user->roleIn($organization)?->canReview();
    }

    public function view(User $user, PracticalSubmission $submission): bool
    {
        return (bool) $user->roleIn($submission->organization)?->canReview();
    }

    /** Проверяющий организации — автора теста; своё решение проверять нельзя. */
    public function update(User $user, PracticalSubmission $submission): bool
    {
        return $this->view($user, $submission) && $submission->attempt->user_id !== $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, PracticalSubmission $submission): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
