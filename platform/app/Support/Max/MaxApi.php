<?php

namespace App\Support\Max;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Клиент API бота MAX.
 *
 * @see https://dev.max.ru/docs-api
 */
class MaxApi
{
    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl,
    ) {}

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /** Сведения о боте: быстрая проверка токена. */
    public function me(): array
    {
        return $this->request()->get('/me')->throw()->json();
    }

    /**
     * Сообщение пользователю в личный диалог.
     *
     * @param  list<array<string, mixed>>  $buttons  кнопки одной колонкой
     *
     * @throws RequestException
     */
    public function sendMessage(int $maxUserId, string $text, array $buttons = []): array
    {
        $payload = ['text' => mb_substr($text, 0, 4000)];

        if ($buttons !== []) {
            $payload['attachments'] = [[
                'type' => 'inline_keyboard',
                'payload' => ['buttons' => array_map(fn (array $button) => [$button], $buttons)],
            ]];
        }

        return $this->request()->post('/messages?user_id='.$maxUserId, $payload)->throw()->json();
    }

    /** Кнопка-ссылка: открывает мини-приложение по диплинку. */
    public static function appButton(string $text, ?string $startParam = null): array
    {
        $bot = (string) config('services.max.bot_name');
        $url = "https://max.ru/{$bot}?startapp".($startParam ? '='.$startParam : '');

        return ['type' => 'link', 'text' => $text, 'url' => $url];
    }

    /** @param list<string> $updateTypes */
    public function subscribe(string $url, array $updateTypes, ?string $secret = null): array
    {
        return $this->request()->post('/subscriptions', array_filter([
            'url' => $url,
            'update_types' => $updateTypes,
            'secret' => $secret ?: null,
        ]))->throw()->json();
    }

    public function subscriptions(): array
    {
        return $this->request()->get('/subscriptions')->throw()->json();
    }

    public function unsubscribe(string $url): array
    {
        return $this->request()->delete('/subscriptions', ['url' => $url])->throw()->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeader('Authorization', $this->token)
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(5);
    }
}
