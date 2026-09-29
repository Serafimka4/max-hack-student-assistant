<?php

namespace App\Jobs;

use App\Support\Max\MaxApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/** Обработка события бота: приветствие и подсказка по открытию мини-приложения. */
class HandleMaxUpdate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param array<string, mixed> $update */
    public function __construct(private readonly array $update) {}

    public function handle(MaxApi $api): void
    {
        $type = $this->update['update_type'] ?? null;
        $maxUserId = $this->update['user']['id'] ?? $this->update['message']['sender']['user_id'] ?? null;

        if (! $maxUserId || ! $api->isConfigured()) {
            return;
        }

        $text = match ($type) {
            'bot_started' => "Это помощник студента: расписание, практики и стажировки, диагностика навыков, шаблоны документов и обращения.\n\n"
                .'Откройте приложение кнопкой ниже — вход выполнится автоматически.',
            'message_created' => $this->reply((string) ($this->update['message']['body']['text'] ?? '')),
            default => null,
        };

        if ($text === null) {
            return;
        }

        try {
            $api->sendMessage((int) $maxUserId, $text, [
                MaxApi::appButton('Открыть приложение'),
                MaxApi::appButton('Расписание', 'schedule'),
                MaxApi::appButton('Практики', 'career'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Не удалось ответить на событие MAX', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /** В MVP бот не ведёт диалог: он открывает нужный раздел мини-приложения. */
    private function reply(string $incoming): string
    {
        $text = mb_strtolower($incoming);

        return match (true) {
            str_contains($text, 'расписан') => 'Расписание на неделю — в разделе «Расписание».',
            str_contains($text, 'практик') || str_contains($text, 'стажир') => 'Практики и статусы заявок — в разделе «Карьера».',
            str_contains($text, 'справк') || str_contains($text, 'документ') => 'Шаблоны документов и частые вопросы — в разделе «Помощь».',
            default => 'Всё в мини-приложении: расписание, практики, диагностика навыков и обращения.',
        };
    }
}
