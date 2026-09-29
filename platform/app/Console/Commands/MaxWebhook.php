<?php

namespace App\Console\Commands;

use App\Support\Max\MaxApi;
use Illuminate\Console\Command;

class MaxWebhook extends Command
{
    protected $signature = 'max:webhook
        {action=show : show, subscribe или unsubscribe}
        {--url= : адрес вебхука; по умолчанию APP_URL/max/webhook}';

    protected $description = 'Подписка бота MAX на события (вебхук)';

    public function handle(MaxApi $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('MAX_BOT_TOKEN не задан.');

            return self::FAILURE;
        }

        $url = $this->option('url') ?: rtrim((string) config('app.url'), '/').'/max/webhook';

        return match ($this->argument('action')) {
            'show' => $this->show($api),
            'subscribe' => $this->subscribe($api, $url),
            'unsubscribe' => $this->unsubscribe($api, $url),
            default => tap(self::FAILURE, fn () => $this->error('Неизвестное действие.')),
        };
    }

    private function show(MaxApi $api): int
    {
        $bot = $api->me();
        $this->info("Бот: {$bot['name']} (@{$bot['username']})");

        $subscriptions = $api->subscriptions()['subscriptions'] ?? [];

        if ($subscriptions === []) {
            $this->warn('Подписок нет. Выполните: php artisan max:webhook subscribe');

            return self::SUCCESS;
        }

        $this->table(['URL', 'События'], array_map(
            fn (array $row) => [$row['url'] ?? '', implode(', ', $row['update_types'] ?? [])],
            $subscriptions,
        ));

        return self::SUCCESS;
    }

    private function subscribe(MaxApi $api, string $url): int
    {
        if (! str_starts_with($url, 'https://')) {
            $this->error('MAX принимает только HTTPS-адрес на порту 443.');

            return self::FAILURE;
        }

        $api->subscribe($url, ['bot_started', 'message_created'], (string) config('services.max.webhook_secret'));
        $this->info("Подписка создана: {$url}");

        return self::SUCCESS;
    }

    private function unsubscribe(MaxApi $api, string $url): int
    {
        $api->unsubscribe($url);
        $this->info("Подписка удалена: {$url}");

        return self::SUCCESS;
    }
}
