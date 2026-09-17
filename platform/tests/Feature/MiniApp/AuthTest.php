<?php

namespace Tests\Feature\MiniApp;

use App\Models\InternshipOffer;
use App\Models\User;
use Tests\Unit\InitDataValidatorTest;

class AuthTest extends MiniAppTestCase
{
    public function test_guest_is_sent_to_start_screen(): void
    {
        $this->get('/app')->assertRedirect('/app/start');
        $this->get('/app/start')->assertOk()->assertSee('Откройте приложение в MAX');
    }

    public function test_max_login_creates_session_enrolls_pilot_student_and_follows_deeplink(): void
    {
        config(['services.max.bot_token' => 'test-bot-token', 'miniapp.enroll_organization' => 'demo-university']);
        $offer = InternshipOffer::firstOrFail();
        $params = ['start_param' => "offer_{$offer->id}"] + InitDataValidatorTest::params();

        $this->postJson('/app/auth', ['init_data' => InitDataValidatorTest::sign($params)])
            ->assertOk()
            ->assertJsonPath('redirect', route('miniapp.offers.show', $offer));

        $user = User::where('max_user_id', 67890)->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('demo-university', $user->student->organization->slug);
        $this->get('/app')->assertOk();
    }

    public function test_max_login_without_pilot_enrollment_has_no_access(): void
    {
        config(['services.max.bot_token' => 'test-bot-token', 'miniapp.enroll_organization' => null]);

        $this->postJson('/app/auth', ['init_data' => InitDataValidatorTest::sign(InitDataValidatorTest::params())])->assertOk();

        $this->get('/app')->assertRedirect('/app/start?reason=no-access');
    }

    public function test_rejects_forged_init_data(): void
    {
        config(['services.max.bot_token' => 'test-bot-token']);

        $this->postJson('/app/auth', ['init_data' => InitDataValidatorTest::sign(InitDataValidatorTest::params(), 'forged')])
            ->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_demo_login_is_available_only_when_enabled(): void
    {
        config(['miniapp.demo_login' => false]);
        $this->postJson('/app/demo-login')->assertNotFound();

        config(['miniapp.demo_login' => true]);
        $this->postJson('/app/demo-login')->assertOk();
        $this->assertAuthenticatedAs($this->student);
    }
}
