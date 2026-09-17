<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\Unit\InitDataValidatorTest;

class MaxAuthTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.max.bot_token' => 'test-bot-token']);
    }

    public function test_issues_token_for_valid_init_data_and_creates_user_once(): void
    {
        $initData = InitDataValidatorTest::sign(InitDataValidatorTest::params());

        $this->postJson('/api/v1/auth/max', ['init_data' => $initData])
            ->assertOk()
            ->assertJsonPath('user.name', 'Max User')
            ->assertJsonPath('start_param', 'offer_42')
            ->assertJsonStructure(['token']);

        $this->postJson('/api/v1/auth/max', ['init_data' => $initData])->assertOk();

        $this->assertSame(1, User::where('max_user_id', 67890)->count());
    }

    public function test_token_authenticates_api_requests(): void
    {
        $token = $this->postJson('/api/v1/auth/max', [
            'init_data' => InitDataValidatorTest::sign(InitDataValidatorTest::params()),
        ])->json('token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('name', 'Max User');
    }

    public function test_rejects_invalid_signature(): void
    {
        $initData = InitDataValidatorTest::sign(InitDataValidatorTest::params(), 'wrong-token');

        $this->postJson('/api/v1/auth/max', ['init_data' => $initData])->assertUnauthorized();
        $this->assertDatabaseCount('users', 0);
    }
}
