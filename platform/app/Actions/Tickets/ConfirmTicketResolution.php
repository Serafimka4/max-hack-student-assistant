<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ConfirmTicketResolution
{
    public function __invoke(Ticket $ticket, User $user): Ticket
    {
        if ($ticket->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        if ($ticket->status !== TicketStatus::Resolved) {
            throw new DomainRuleException('Подтвердить можно только решённое обращение.');
        }

        $ticket->forceFill(['confirmed_at' => $ticket->confirmed_at ?? now()])->save();

        return $ticket;
    }
}
