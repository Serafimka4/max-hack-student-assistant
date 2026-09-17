<?php

namespace Tests\Feature\MiniApp;

use App\Enums\TicketStatus;
use App\Livewire\MiniApp\Help;
use App\Livewire\MiniApp\ReportSheet;
use App\Models\FaqItem;
use App\Models\InternshipApplication;
use App\Models\InternshipOffer;
use App\Models\Lesson;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;

class HelpTest extends MiniAppTestCase
{
    public function test_ticket_context_and_assignee_come_from_server(): void
    {
        $this->actingAs($this->student);
        $lesson = Lesson::where('title', 'Базы данных')->firstOrFail();

        Livewire::test(ReportSheet::class)
            ->dispatch('open-report', category: 'schedule', subjectType: 'lesson', subjectId: $lesson->id)
            ->assertSet('open', true)
            ->assertSee('Базы данных, пара 1 · б-ИФСТ-31')
            ->set('body', 'Аудитория закрыта, пара перенесена?')
            ->call('submit')
            ->assertSet('open', false)
            ->assertDispatched('ticket-created');

        $ticket = Ticket::latest('id')->firstOrFail();
        $this->assertSame('Учебный отдел', $ticket->assignee);
        $this->assertSame(TicketStatus::New, $ticket->status);
        $this->assertTrue($ticket->subject->is($lesson));
    }

    public function test_cannot_attach_ticket_to_someone_elses_application(): void
    {
        $other = User::factory()->create();
        $other->student()->create(['organization_id' => $this->student->student->organization_id]);
        $application = InternshipApplication::firstOrFail()->replicate()->fill(['user_id' => $other->id]);
        $application->internship_offer_id = InternshipOffer::where('title', 'Frontend-стажёр')->value('id');
        $application->save();

        $this->actingAs($this->student);

        Livewire::test(ReportSheet::class)
            ->dispatch('open-report', category: 'internship', subjectType: 'application', subjectId: $application->id)
            ->assertForbidden();
    }

    public function test_body_is_required(): void
    {
        $this->actingAs($this->student);

        Livewire::test(ReportSheet::class)
            ->dispatch('open-report', category: 'other')
            ->call('submit')
            ->assertHasErrors('body');
    }

    public function test_not_helpful_answer_opens_report_and_rating_is_saved_once(): void
    {
        $this->actingAs($this->student);
        $item = FaqItem::firstOrFail();

        Livewire::test(Help::class)
            ->call('rate', $item->id, false)
            ->assertDispatched('open-report', category: 'faq', subjectType: 'faq_item', subjectId: $item->id)
            ->call('rate', $item->id, true);

        $this->assertDatabaseCount('faq_feedback', 1);
        $this->assertDatabaseHas('faq_feedback', ['faq_item_id' => $item->id, 'helpful' => true]);
    }

    public function test_search_filters_faq_and_resolution_can_be_confirmed(): void
    {
        $this->actingAs($this->student);
        $resolved = $this->student->tickets()->where('status', TicketStatus::Resolved)->firstOrFail();

        Livewire::test(Help::class)
            ->set('q', 'стипенд')
            ->assertSee('Куда обращаться по вопросам стипендии?')
            ->assertDontSee('Как получить справку об обучении?')
            ->call('confirm', $resolved->id)
            ->assertDispatched('toast', message: 'Спасибо! Решение подтверждено');

        $this->assertNotNull($resolved->fresh()->confirmed_at);
    }
}
