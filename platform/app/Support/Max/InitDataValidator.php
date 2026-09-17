<?php

namespace App\Support\Max;

use Carbon\CarbonImmutable;

/**
 * Проверка подписи стартовых данных мини-приложения MAX.
 *
 * @see https://dev.max.ru/docs/webapps/validation
 */
class InitDataValidator
{
    public function __construct(
        private readonly string $botToken,
        private readonly int $maxAgeSeconds = 3600,
    ) {}

    /**
     * @return array{user: array<string, mixed>, chat: ?array<string, mixed>, start_param: ?string, auth_date: CarbonImmutable, query_id: ?string}
     *
     * @throws InvalidInitDataException
     */
    public function validate(string $initData): array
    {
        if ($this->botToken === '') {
            throw new InvalidInitDataException('MAX_BOT_TOKEN не задан.');
        }

        $params = [];
        foreach (explode('&', $initData) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if (array_key_exists($key, $params)) {
                throw new InvalidInitDataException("Параметр {$key} повторяется.");
            }
            $params[$key] = rawurldecode($value);
        }

        $hash = $params['hash'] ?? null;
        if (! is_string($hash) || $hash === '') {
            throw new InvalidInitDataException('Нет подписи.');
        }
        unset($params['hash']);
        ksort($params, SORT_STRING);

        $launchParams = implode("\n", array_map(fn ($k, $v) => "{$k}={$v}", array_keys($params), $params));
        $secret = hash_hmac('sha256', $this->botToken, 'WebAppData', true);

        if (! hash_equals(hash_hmac('sha256', $launchParams, $secret), $hash)) {
            throw new InvalidInitDataException('Подпись не совпадает.');
        }

        $authDate = CarbonImmutable::createFromTimestamp((int) ($params['auth_date'] ?? 0));
        if ($authDate->addSeconds($this->maxAgeSeconds)->isPast()) {
            throw new InvalidInitDataException('Данные запуска устарели.');
        }

        $user = json_decode($params['user'] ?? '', true);
        if (! is_array($user) || ! isset($user['id'])) {
            throw new InvalidInitDataException('Нет данных пользователя.');
        }

        return [
            'user' => $user,
            'chat' => isset($params['chat']) ? json_decode($params['chat'], true) : null,
            'start_param' => $params['start_param'] ?? null,
            'auth_date' => $authDate,
            'query_id' => $params['query_id'] ?? null,
        ];
    }
}
