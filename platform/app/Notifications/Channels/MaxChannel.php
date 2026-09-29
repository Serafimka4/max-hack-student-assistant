<?php

namespace App\Notifications\Channels;

use App\Support\Max\MaxApi;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Доставка уведомлений через бота MAX.
 * Сбой отправки не отменяет сохранённое действие пользователя — ошибка только логируется.
 */
class MaxChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        // Канал кешируется менеджером уведомлений, поэтому клиент берём на каждой отправке.
        $api = app(MaxApi::class);
        $maxUserId = $notifiable->routeNotificationFor('max', $notification);

        if (! $maxUserId || ! $api->isConfigured() || ! config('services.max.notifications')) {
            return;
        }

        /** @var MaxMessage $message */
        $message = $notification->toMax($notifiable);

        try {
            $api->sendMessage((int) $maxUserId, $message->text, $message->buttons);
        } catch (\Throwable $e) {
            Log::warning('Не удалось отправить уведомление в MAX', [
                'user_id' => $notifiable->getKey(),
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
