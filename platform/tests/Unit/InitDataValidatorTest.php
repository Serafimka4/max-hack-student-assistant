<?php

namespace Tests\Unit;

use App\Support\Max\InitDataValidator;
use App\Support\Max\InvalidInitDataException;
use PHPUnit\Framework\TestCase;

class InitDataValidatorTest extends TestCase
{
    private const TOKEN = 'test-bot-token';

    /** Подписывает параметры по алгоритму из документации MAX. */
    public static function sign(array $params, string $token = self::TOKEN): string
    {
        ksort($params, SORT_STRING);
        $launch = implode("\n", array_map(fn ($k, $v) => "{$k}={$v}", array_keys($params), $params));
        $secret = hash_hmac('sha256', $token, 'WebAppData', true);
        $params['hash'] = hash_hmac('sha256', $launch, $secret);

        return implode('&', array_map(fn ($k, $v) => $k.'='.rawurlencode($v), array_keys($params), $params));
    }

    public static function params(?int $authDate = null): array
    {
        return [
            'auth_date' => (string) ($authDate ?? time()),
            'query_id' => '4c0ab423-342b-4e45-aea4-2747dbc500cd',
            'user' => json_encode(['id' => 67890, 'first_name' => 'Max', 'last_name' => 'User'], JSON_UNESCAPED_UNICODE),
            'chat' => json_encode(['id' => 12345, 'type' => 'DIALOG']),
            'start_param' => 'offer_42',
        ];
    }

    public function test_accepts_correctly_signed_data(): void
    {
        $data = (new InitDataValidator(self::TOKEN))->validate(self::sign(self::params()));

        $this->assertSame(67890, $data['user']['id']);
        $this->assertSame('offer_42', $data['start_param']);
        $this->assertSame('DIALOG', $data['chat']['type']);
    }

    public function test_rejects_tampered_data(): void
    {
        $initData = str_replace('67890', '11111', self::sign(self::params()));

        $this->expectException(InvalidInitDataException::class);
        (new InitDataValidator(self::TOKEN))->validate($initData);
    }

    public function test_rejects_data_signed_by_another_bot(): void
    {
        $this->expectException(InvalidInitDataException::class);
        (new InitDataValidator(self::TOKEN))->validate(self::sign(self::params(), 'other-token'));
    }

    public function test_rejects_expired_data(): void
    {
        $this->expectException(InvalidInitDataException::class);
        (new InitDataValidator(self::TOKEN, 3600))->validate(self::sign(self::params(time() - 7200)));
    }

    public function test_rejects_duplicated_parameters(): void
    {
        $this->expectException(InvalidInitDataException::class);
        (new InitDataValidator(self::TOKEN))->validate(self::sign(self::params()).'&auth_date=1');
    }
}
