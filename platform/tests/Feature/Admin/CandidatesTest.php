<?php

namespace Tests\Feature\Admin;

use App\Enums\SkillLevel;
use App\Filament\Pages\Candidates;
use App\Models\InternshipApplication;
use App\Models\InternshipOffer;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Feature\MiniApp\MiniAppTestCase;

class CandidatesTest extends MiniAppTestCase
{
    private Organization $employer;

    private User $hr;

    private InternshipOffer $offer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employer = Organization::where('slug', 'demo-employer')->firstOrFail();
        $this->hr = User::where('email', 'hr@demo.test')->firstOrFail();
        $this->offer = InternshipOffer::where('title', 'Frontend-стажёр')->firstOrFail();
    }

    private function candidates(): Testable
    {
        $this->actingAs($this->hr);
        Filament::setTenant($this->employer);

        return Livewire::test(Candidates::class);
    }

    public function test_employer_sees_only_shared_applications_to_own_offers(): void
    {
        $shared = InternshipApplication::where('internship_offer_id', $this->offer->id)->sole();
        $hidden = $this->offer->applications()->create(['user_id' => $this->student->id, 'status' => 'submitted', 'share_results' => false]);
        $foreign = InternshipApplication::where('internship_offer_id', '!=', $this->offer->id)->firstOrFail();

        $this->candidates()
            ->assertCanSeeTableRecords([$shared])
            ->assertCanNotSeeTableRecords([$hidden, $foreign])
            ->assertSee('Стажёр');
    }

    public function test_skill_filter_uses_verified_levels(): void
    {
        $js = Skill::where('title', 'JavaScript')->firstOrFail();
        $shared = InternshipApplication::where('internship_offer_id', $this->offer->id)->sole();

        // Мария ответила на все вопросы верно: базовый уровень подтверждён.
        $this->candidates()
            ->filterTable('skill', ['skill_id' => $js->id, 'level' => SkillLevel::Basic->value])
            ->assertCanSeeTableRecords([$shared])
            ->filterTable('skill', ['skill_id' => $js->id, 'level' => SkillLevel::Applied->value])
            ->assertCanNotSeeTableRecords([$shared]);
    }

    public function test_candidate_card_shows_grounds_without_answer_keys(): void
    {
        $shared = InternshipApplication::where('internship_offer_id', $this->offer->id)->sole();

        $page = $this->candidates()->assertActionVisible(TestAction::make('card')->table($shared));
        $card = $page->instance()->candidateCard($shared->fresh());

        $this->assertStringContainsString('Предварительный грейд: Стажёр', $card);
        $this->assertStringContainsString('Требования предложения:', $card);
        $this->assertStringContainsString('HTML / CSS — нужно прикладной; подтверждён только базовый', $card);
        $this->assertStringContainsString('Оценка предварительная', $card);
        $this->assertStringNotContainsString('correct_keys', $card);
    }

    public function test_interest_is_recorded_and_passed_to_coordinator(): void
    {
        $shared = InternshipApplication::where('internship_offer_id', $this->offer->id)->sole();

        $this->candidates()
            ->callAction(TestAction::make('interest')->table($shared), ['note' => 'Готовы пригласить на собеседование'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('employer_interests', [
            'internship_application_id' => $shared->id,
            'organization_id' => $this->employer->id,
            'created_by' => $this->hr->id,
            'note' => 'Готовы пригласить на собеседование',
        ]);

        // Координатор видит отметку в списке заявок.
        $this->flushSession();
        $this->actingAs(User::where('email', 'coordinator@demo.test')->firstOrFail());
        $university = $this->student->student->organization;
        $this->get("/admin/{$university->slug}/internship-applications")->assertOk();
    }

    public function test_reviewer_of_employer_cannot_mark_interest(): void
    {
        $shared = InternshipApplication::where('internship_offer_id', $this->offer->id)->sole();
        $reviewer = User::where('email', 'reviewer@demo.test')->firstOrFail();

        $this->actingAs($reviewer);
        Filament::setTenant($this->employer);

        Livewire::test(Candidates::class)
            ->assertCanSeeTableRecords([$shared])
            ->assertActionHidden(TestAction::make('interest')->table($shared));
    }

    public function test_page_is_closed_for_university_cabinet_and_outsiders(): void
    {
        $university = $this->student->student->organization;

        $this->actingAs($this->hr);
        Filament::setTenant($university);
        $this->assertFalse(Candidates::canAccess());

        $this->flushSession();
        $this->actingAs($this->student);
        Filament::setTenant($this->employer);
        $this->assertFalse(Candidates::canAccess());
    }
}
