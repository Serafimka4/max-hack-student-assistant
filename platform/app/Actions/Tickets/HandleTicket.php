<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Models\Ticket;
use App\Models\User;

/** Работа сотрудника с обращением: взять в работу и зафиксировать решение. */
class HandleTicket
{
    public function takeInProgress(Ticket $ticket, User $staff, ?string $assignee = null): Ticket
    {
        if ($ticket->status === TicketStatus::Resolved) {
            throw new DomainRuleException('Обращение уже решено. Откройте его повторно, если проблема осталась.');
        }

        $ticket->update([
            'status' => TicketStatus::InProgress,
            'handled_by' => $staff->id,
            'assignee' => filled($assignee) ? trim($assignee) : $ticket->assignee,
        ]);

        return $ticket;
    }

    public function resolve(Ticket $ticket, User $staff, string $resolution): Ticket
    {
        if (blank($resolution)) {
            throw new DomainRuleException('Опишите решение — студент увидит этот текст.');
        }

        $ticket->forceFill([
            'status' => TicketStatus::Resolved,
            'handled_by' => $staff->id,
            'resolution' => trim($resolution),
            'resolved_at' => now(),
            // Подтверждение студента запрашивается заново после каждого решения.
            'confirmed_at' => null,
        ])->save();

        return $ticket;
    }

    /** Повторное открытие, если проблема осталась. */
    public function reopen(Ticket $ticket, User $staff, string $reason): Ticket
    {
        if ($ticket->status !== TicketStatus::Resolved) {
            throw new DomainRuleException('Повторно открыть можно только решённое обращение.');
        }

        $ticket->forceFill([
            'status' => TicketStatus::InProgress,
            'handled_by' => $staff->id,
            'resolution' => trim($ticket->resolution."\n\nОткрыто повторно: ".trim($reason)),
            'resolved_at' => null,
            'confirmed_at' => null,
        ])->save();

        return $ticket;
    }
}
