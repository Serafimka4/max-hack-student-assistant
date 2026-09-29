<?php

namespace App\Notifications;

use App\Models\AssessmentAttempt;
use App\Notifications\Channels\MaxMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PracticalReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly AssessmentAttempt $attempt) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['max'];
    }

    public function toMax(object $notifiable): MaxMessage
    {
        $title = $this->attempt->version->assessment->title;

        return MaxMessage::make(
            "Практическое задание по тесту «{$title}» проверено. Профиль навыков обновлён."
        )->appButton('Посмотреть результат', 'attempt_'.$this->attempt->id);
    }
}
