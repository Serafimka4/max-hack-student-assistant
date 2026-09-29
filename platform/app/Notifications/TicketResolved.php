<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Notifications\Channels\MaxMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketResolved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['max'];
    }

    public function toMax(object $notifiable): MaxMessage
    {
        return MaxMessage::make(
            "Обращение «{$this->ticket->title}» решено.\n\n{$this->ticket->resolution}\n\n"
            .'Если проблема осталась, откройте раздел «Помощь» и сообщите об этом.'
        )->appButton('Подтвердить решение', 'help');
    }
}
