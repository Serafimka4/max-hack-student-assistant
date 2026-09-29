<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\Concerns\ResolvesOrganization;

class TicketPolicy
{
    use ResolvesOrganization;

    public function viewAny(User $user, ?Organization $organization = null): bool
    {
        $organization = $this->organization($organization);

        return $organization !== null && (bool) $user->roleIn($organization)?->canCoordinate();
    }

    /** Обращение конфиденциально: автор и сотрудники своей организации. */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || (bool) $user->roleIn($ticket->organization)?->canCoordinate();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return (bool) $user->roleIn($ticket->organization)?->canCoordinate();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
