<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\InternshipApplication;
use App\Notifications\Channels\MaxMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ApplicationDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly InternshipApplication $application,
        private readonly ApplicationStatus $status,
        private readonly ?string $comment,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['max'];
    }

    public function toMax(object $notifiable): MaxMessage
    {
        $offer = $this->application->offer;
        $text = match ($this->status) {
            ApplicationStatus::InReview => "Заявка на практику «{$offer->title}» ({$offer->company_name}) принята к рассмотрению.",
            ApplicationStatus::Accepted => "Вас приняли на практику «{$offer->title}» ({$offer->company_name}).",
            ApplicationStatus::Rejected => "По заявке «{$offer->title}» ({$offer->company_name}) получен отказ.",
            default => "Статус заявки «{$offer->title}»: {$this->status->getLabel()}.",
        };

        if ($this->comment) {
            $text .= "\n\nКомментарий: {$this->comment}";
        }

        return MaxMessage::make($text)->appButton('Открыть заявку', 'offer_'.$offer->id);
    }
}
