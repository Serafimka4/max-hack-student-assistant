<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class AssessmentPolicy
{
    use ResolvesOrganization;

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && $user->roleIn($organization) !== null;
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->roleIn($assessment->organization) !== null;
    }

    public function create(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && $user->canManageContentOf($organization);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->canManageContentOf($assessment->organization);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->canManageContentOf($assessment->organization);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }
}
