<?php

namespace Tests\Feature\MiniApp;

use App\Enums\ApplicationStatus;
use App\Livewire\MiniApp\OfferShow;
use App\Models\InternshipOffer;
use App\Models\Organization;
use Livewire\Livewire;

class ApplicationsTest extends MiniAppTestCase
{
    private function frontendOffer(): InternshipOffer
    {
        return InternshipOffer::where('title', 'Frontend-стажёр')->firstOrFail();
    }

    public function test_repeated_apply_does_not_duplicate_and_withdraw_allows_reapply(): void
    {
        $this->actingAs($this->student);
        $offer = $this->frontendOffer();

        Livewire::test(OfferShow::class, ['offer' => $offer])
            ->set('shareResults', false)
            ->call('apply')
            ->call('apply')
            ->assertDispatched('toast')
            ->assertSee('Ваша заявка');

        $application = $offer->applications()->where('user_id', $this->student->id)->sole();
        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertFalse($application->share_results);
        $this->assertCount(1, $application->history);

        Livewire::test(OfferShow::class, ['offer' => $offer])->call('withdraw');
        $this->assertSame(ApplicationStatus::Withdrawn, $application->fresh()->status);

        Livewire::test(OfferShow::class, ['offer' => $offer])->call('apply');
        $this->assertSame(ApplicationStatus::Submitted, $application->fresh()->status);
        $this->assertSame(1, $offer->applications()->where('user_id', $this->student->id)->count());
        $this->assertCount(3, $application->fresh()->history);
    }

    public function test_decided_application_cannot_be_withdrawn(): void
    {
        $this->actingAs($this->student);
        $offer = $this->frontendOffer();
        $offer->applications()->create(['user_id' => $this->student->id, 'status' => ApplicationStatus::Accepted]);

        Livewire::test(OfferShow::class, ['offer' => $offer])
            ->call('withdraw')
            ->assertDispatched('toast', message: 'Заявку с решением отозвать нельзя. Обратитесь к координатору.', tone: 'error');
    }

    public function test_closed_offer_rejects_application(): void
    {
        $this->actingAs($this->student);
        $offer = $this->frontendOffer();
        $offer->update(['apply_until' => now()->subDay()]);

        Livewire::test(OfferShow::class, ['offer' => $offer])
            ->call('apply')
            ->assertDispatched('toast', tone: 'error');
        $this->assertSame(0, $offer->applications()->where('user_id', $this->student->id)->count());
    }

    public function test_offers_of_other_universities_and_drafts_are_hidden(): void
    {
        $this->actingAs($this->student);
        $foreign = Organization::factory()->create()->internshipOffers()->create([
            'company_name' => 'X', 'title' => 'Чужое', 'direction' => 'dev', 'format' => 'Офис', 'tasks' => [], 'is_published' => true,
        ]);
        $draft = $this->frontendOffer()->replicate()->fill(['is_published' => false]);
        $draft->save();

        $this->get(route('miniapp.offers.show', $foreign))->assertNotFound();
        $this->get(route('miniapp.offers.show', $draft))->assertNotFound();
    }
}
