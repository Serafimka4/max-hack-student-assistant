<?php

namespace Tests\Feature\Admin;

use App\Enums\OfferDirection;
use App\Enums\SkillLevel;
use App\Filament\Resources\InternshipOffers\Pages\CreateInternshipOffer;
use App\Filament\Resources\InternshipOffers\Pages\EditInternshipOffer;
use App\Models\InternshipOffer;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Feature\MiniApp\MiniAppTestCase;

class OffersAdminTest extends MiniAppTestCase
{
    private Organization $university;

    private User $coordinator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->university = $this->student->student->organization;
        $this->coordinator = User::where('email', 'coordinator@demo.test')->firstOrFail();
        $this->actingAs($this->coordinator);
        Filament::setCurrentPanel('admin');
        Filament::setTenant($this->university);
    }

    public function test_coordinator_creates_offer_with_required_skills_and_student_sees_it(): void
    {
        $sql = Skill::where('title', 'SQL')->firstOrFail();
        $python = Skill::where('title', 'Python')->firstOrFail();

        Livewire::test(CreateInternshipOffer::class)
            ->fillForm([
                'title' => 'Стажёр по данным',
                'company_name' => 'Демо-партнёр',
                'direction' => OfferDirection::Data->value,
                'format' => 'Гибрид',
                'places' => 2,
                'apply_until' => now()->addMonth()->toDateString(),
                'tasks' => [['task' => 'Отчёты в BI']],
                'is_published' => true,
                'requirements' => [
                    ['skill_id' => $sql->id, 'level' => SkillLevel::Applied->value],
                    ['skill_id' => $python->id, 'level' => SkillLevel::Basic->value],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = InternshipOffer::where('title', 'Стажёр по данным')->sole();
        $this->assertSame($this->university->id, $offer->organization_id);
        $this->assertSame(
            [$sql->id => SkillLevel::Applied, $python->id => SkillLevel::Basic],
            $offer->skills->mapWithKeys(fn ($s) => [$s->id => $s->pivot->level])->all(),
        );

        $this->flushSession();
        $this->actingAs($this->student);
        $this->get('/app/career')->assertOk()->assertSee('Стажёр по данным');
    }

    public function test_requirements_are_replaced_on_edit(): void
    {
        $offer = InternshipOffer::where('title', 'UX/UI-дизайнер')->firstOrFail();
        $git = Skill::where('title', 'Git')->firstOrFail();

        Livewire::test(EditInternshipOffer::class, ['record' => $offer->getRouteKey()])
            ->assertFormSet(fn (array $data) => count($data['requirements']) === 2)
            ->fillForm(['requirements' => [['skill_id' => $git->id, 'level' => SkillLevel::Basic->value]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$git->id], $offer->refresh()->skills->pluck('id')->all());
    }

    public function test_offer_with_applications_cannot_be_deleted(): void
    {
        $withApplications = InternshipOffer::has('applications')->firstOrFail();
        $free = InternshipOffer::doesntHave('applications')->firstOrFail();

        $this->assertFalse($this->coordinator->can('delete', $withApplications));
        $this->assertTrue($this->coordinator->can('delete', $free));
    }
}
