<?php

namespace Tests\Feature\Max;

use App\Actions\Applications\ChangeApplicationStatus;
use App\Actions\Tickets\HandleTicket;
use App\Enums\ApplicationStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\MiniApp\AuthController;
use App\Jobs\HandleMaxUpdate;
use App\Models\InternshipApplication;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Max\MaxApi;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Tests\Feature\MiniApp\MiniAppTestCase;

class BotTest extends MiniAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.max.bot_token' => 'test-bot-token',
            'services.max.bot_name' => 'demo_bot',
            'services.max.webhook_secret' => 'super-secret',
            'services.max.api_url' => 'https://platform-api2.max.ru',
        ]);
        Http::preventStrayRequests();
        Http::fake(['platform-api2.max.ru/*' => Http::response(['message' => ['body' => ['mid' => 'm1']]])]);
    }

    public function test_webhook_requires_matching_secret(): void
    {
        Queue::fake();

        $this->postJson('/max/webhook', ['update_type' => 'bot_started', 'user' => ['id' => 1]])
            ->assertUnauthorized();

        $this->withHeader('X-Max-Bot-Api-Secret', 'wrong')
            ->postJson('/max/webhook', ['update_type' => 'bot_started', 'user' => ['id' => 1]])
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_webhook_accepts_update_and_queues_handling(): void
    {
        Queue::fake();

        $this->withHeader('X-Max-Bot-Api-Secret', 'super-secret')
            ->postJson('/max/webhook', ['update_type' => 'bot_started', 'user' => ['id' => 67890]])
            ->assertOk()
            ->assertExactJson(['success' => true]);

        Queue::assertPushed(HandleMaxUpdate::class);
    }

    public function test_bot_start_answers_with_deep_link_buttons(): void
    {
        (new HandleMaxUpdate(['update_type' => 'bot_started', 'user' => ['id' => 67890]]))->handle(app(MaxApi::class));

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            $buttons = $body['attachments'][0]['payload']['buttons'];

            return str_contains($request->url(), '/messages?user_id=67890')
                && $request->header('Authorization') === ['test-bot-token']
                && str_contains($body['text'], 'помощник студента')
                && $buttons[0][0]['url'] === 'https://max.ru/demo_bot?startapp'
                && $buttons[1][0]['url'] === 'https://max.ru/demo_bot?startapp=schedule';
        });
    }

    public function test_incoming_message_gets_hint_for_section(): void
    {
        (new HandleMaxUpdate([
            'update_type' => 'message_created',
            'message' => ['sender' => ['user_id' => 555], 'body' => ['text' => 'где расписание?']],
        ]))->handle(app(MaxApi::class));

        Http::assertSent(fn (Request $request) => str_contains($request->data()['text'], 'Расписание на неделю'));
    }

    public function test_student_is_notified_about_decision_with_link_to_application(): void
    {
        $this->student->forceFill(['max_user_id' => 4242])->save();
        $application = InternshipApplication::where('user_id', $this->student->id)->firstOrFail();

        app(ChangeApplicationStatus::class)(
            $application,
            User::where('email', 'coordinator@demo.test')->firstOrFail(),
            ApplicationStatus::Rejected,
            'Нет свободных мест',
        );

        Http::assertSent(function (Request $request) use ($application) {
            $body = $request->data();

            return str_contains($request->url(), 'user_id=4242')
                && str_contains($body['text'], 'получен отказ')
                && str_contains($body['text'], 'Нет свободных мест')
                && $body['attachments'][0]['payload']['buttons'][0][0]['url']
                    === 'https://max.ru/demo_bot?startapp=offer_'.$application->internship_offer_id;
        });
    }

    public function test_resolved_ticket_notifies_student(): void
    {
        $this->student->forceFill(['max_user_id' => 4242])->save();
        $ticket = Ticket::where('user_id', $this->student->id)->where('status', TicketStatus::InProgress)->firstOrFail();

        app(HandleTicket::class)->resolve(
            $ticket,
            User::where('email', 'coordinator@demo.test')->firstOrFail(),
            'Аудитория указана',
        );

        Http::assertSent(fn (Request $request) => str_contains($request->data()['text'], 'Аудитория указана'));
    }

    public function test_notifications_are_skipped_without_token(): void
    {
        config(['services.max.bot_token' => '']);
        $this->student->forceFill(['max_user_id' => 4242])->save();
        $ticket = Ticket::where('user_id', $this->student->id)->where('status', TicketStatus::InProgress)->firstOrFail();

        app(HandleTicket::class)->resolve(
            $ticket,
            User::where('email', 'coordinator@demo.test')->firstOrFail(),
            'Готово',
        );

        Http::assertNothingSent();
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
    }

    public function test_student_without_max_account_does_not_break_the_action(): void
    {
        $ticket = Ticket::where('user_id', $this->student->id)->where('status', TicketStatus::InProgress)->firstOrFail();

        app(HandleTicket::class)->resolve(
            $ticket,
            User::where('email', 'coordinator@demo.test')->firstOrFail(),
            'Готово',
        );

        Http::assertNothingSent();
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
    }

    public function test_deeplink_opens_requested_screen(): void
    {
        $this->actingAs($this->student);
        $attempt = $this->student->attempts()->whereNotNull('submitted_at')->firstOrFail();

        $this->assertSame(route('miniapp.attempts.result', $attempt), $this->redirectFor('attempt_'.$attempt->id));
        $this->assertSame(route('miniapp.schedule'), $this->redirectFor('schedule'));
        $this->assertSame(route('miniapp.home'), $this->redirectFor('unknown'));
    }

    private function redirectFor(string $startParam): string
    {
        $method = new ReflectionMethod(AuthController::class, 'redirectFor');

        return $method->invoke(new AuthController, $startParam);
    }
}
