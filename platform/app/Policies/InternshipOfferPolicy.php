<?php

namespace App\Policies;

use App\Models\InternshipOffer;
use App\Models\Organization;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class InternshipOfferPolicy
{
    use ResolvesOrganization;

    /** Каталог практик ведут координатор и редакторы вуза. */
    private function manages(User $user, ?Organization $organization): bool
    {
        $role = $organization ? $user->roleIn($organization) : null;

        return $role !== null && ($role->canCoordinate() || $role->canManageContent());
    }

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        return $this->manages($user, $this->organization($organization));
    }

    public function view(User $user, InternshipOffer $offer): bool
    {
        return $this->manages($user, $offer->organization);
    }

    public function create(User $user, ?Organization $organization = null): bool
    {
        return $this->manages($user, $this->organization($organization));
    }

    public function update(User $user, InternshipOffer $offer): bool
    {
        return $this->manages($user, $offer->organization);
    }

    public function delete(User $user, InternshipOffer $offer): bool
    {
        return $this->manages($user, $offer->organization) && ! $offer->applications()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
