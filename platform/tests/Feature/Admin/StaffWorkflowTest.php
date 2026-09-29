<?php

namespace Tests\Feature\Admin;

use App\Actions\Applications\ChangeApplicationStatus;
use App\Actions\Tickets\HandleTicket;
use App\Enums\ApplicationStatus;
use App\Enums\MemberRole;
use App\Enums\TicketStatus;
use App\Exceptions\DomainRuleException;
use App\Filament\Resources\InternshipApplications\Pages\ListInternshipApplications;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\InternshipApplication;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Feature\MiniApp\MiniAppTestCase;

class StaffWorkflowTest extends MiniAppTestCase
{
    private Organization $university;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->university = $this->student->student->organization;
        $this->coordinator = User::where('email', 'coordinator@demo.test')->firstOrFail();
    }

    private function application(): InternshipApplication
    {
        return InternshipApplication::where('user_id', $this->student->id)->firstOrFail();
    }

    public function test_coordinator_moves_application_through_statuses(): void
    {
        $application = $this->application();
        $application->update(['status' => ApplicationStatus::Submitted]);
        $application->history()->whereNot('status', ApplicationStatus::Submitted)->delete();

        $this->actingAs($this->coordinator);
        Filament::setTenant($this->university);

        Livewire::test(ListInternshipApplications::class)
            ->assertCanSeeTableRecords([$application])
            ->callAction(TestAction::make('review')->table($application), ['comment' => 'Передали партнёру'])
            ->callAction(TestAction::make('accept')->table($application), ['comment' => 'Место согласовано']);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Accepted, $application->status);
        $this->assertSame(['Подана', 'На рассмотрении', 'Принят'], $application->history->pluck('status')->map->getLabel()->all());
        $this->assertSame($this->coordinator->id, $application->history->last()->changed_by);
    }

    public function test_rejection_requires_reason_and_decided_application_is_final(): void
    {
        $application = $this->application();
        $action = app(ChangeApplicationStatus::class);

        try {
            $action($application, $this->coordinator, ApplicationStatus::Rejected, ' ');
            $this->fail('Отказ без причины должен быть отклонён.');
        } catch (DomainRuleException $e) {
            $this->assertStringContainsString('причину отказа', $e->getMessage());
        }

        $action($application, $this->coordinator, ApplicationStatus::Rejected, 'Нет свободных мест');
        $this->assertSame('Нет свободных мест', $application->history->last()->comment);

        $this->expectException(DomainRuleException::class);
        $action($application->fresh(), $this->coordinator, ApplicationStatus::Accepted);
    }

    public function test_student_sees_decision_and_comment_in_miniapp(): void
    {
        app(ChangeApplicationStatus::class)($this->application(), $this->coordinator, ApplicationStatus::Rejected, 'Нет свободных мест');

        $this->actingAs($this->student);
        $this->get('/app/career?view=applications')->assertOk()->assertSee('Отказ')->assertSee('Нет свободных мест');
    }

    public function test_coordinator_handles_ticket_and_student_confirms(): void
    {
        $ticket = Ticket::where('status', TicketStatus::InProgress)->firstOrFail();
        $ticket->update(['status' => TicketStatus::New]);

        $this->actingAs($this->coordinator);
        Filament::setTenant($this->university);

        Livewire::test(ListTickets::class)
            ->callAction(TestAction::make('take')->table($ticket), ['assignee' => 'Учебный отдел'])
            ->callAction(TestAction::make('resolve')->table($ticket), ['resolution' => 'Аудитория указана, расписание обновлено']);

        $ticket->refresh();
        $this->assertSame(TicketStatus::Resolved, $ticket->status);
        $this->assertSame($this->coordinator->id, $ticket->handled_by);
        $this->assertNotNull($ticket->resolved_at);

        $this->flushSession();
        $this->actingAs($this->student);
        $this->get('/app/help')->assertOk()->assertSee('Аудитория указана, расписание обновлено');
    }

    public function test_reopened_ticket_requires_new_resolution(): void
    {
        $handle = app(HandleTicket::class);
        $ticket = Ticket::where('status', TicketStatus::Resolved)->firstOrFail();
        $ticket->forceFill(['confirmed_at' => now()])->save();

        $handle->reopen($ticket, $this->coordinator, 'Поле так и не появилось');

        $ticket->refresh();
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertNull($ticket->confirmed_at);
        $this->assertNull($ticket->resolved_at);
        $this->assertStringContainsString('Открыто повторно', $ticket->resolution);
    }

    public function test_only_coordinators_of_the_university_reach_staff_screens(): void
    {
        $reviewer = User::factory()->create();
        $reviewer->organizations()->attach($this->university, ['role' => MemberRole::Reviewer]);

        $this->actingAs($reviewer);
        $this->get("/admin/{$this->university->slug}/internship-applications")->assertForbidden();
        $this->get("/admin/{$this->university->slug}/tickets")->assertForbidden();

        $this->flushSession();
        $this->actingAs($this->coordinator);
        $this->get("/admin/{$this->university->slug}/internship-applications")->assertOk();
        $this->get("/admin/{$this->university->slug}/tickets")->assertOk();
        $this->get("/admin/{$this->university->slug}/internship-offers")->assertOk();
    }
}
