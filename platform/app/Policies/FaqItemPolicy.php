<?php

namespace App\Policies;

use App\Models\FaqItem;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class FaqItemPolicy
{
    use ResolvesOrganization;

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && $user->canManageContentOf($organization);
    }

    public function view(User $user, FaqItem $item): bool
    {
        return $user->canManageContentOf($item->organization);
    }

    public function create(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && $user->canManageContentOf($organization);
    }

    public function update(User $user, FaqItem $item): bool
    {
        return $user->canManageContentOf($item->organization);
    }

    public function delete(User $user, FaqItem $item): bool
    {
        return $user->canManageContentOf($item->organization);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
