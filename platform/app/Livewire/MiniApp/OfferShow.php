<?php

namespace App\Livewire\MiniApp;

use App\Actions\Applications\SubmitApplication;
use App\Actions\Applications\WithdrawApplication;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\InternshipOffer;
use App\Support\Skills\SkillMatcher;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.miniapp')]
class OfferShow extends Component
{
    use InteractsWithStudent;

    #[Locked]
    public int $offerId;

    public bool $shareResults = true;

    public function mount(InternshipOffer $offer): void
    {
        abort_unless($offer->is_published && $offer->organization_id === $this->student()->organization_id, 404);
        $this->offerId = $offer->id;
    }

    private function offer(): InternshipOffer
    {
        return InternshipOffer::with('skills')->findOrFail($this->offerId);
    }

    public function apply(SubmitApplication $submit): void
    {
        $this->perform(fn () => $submit($this->offer(), $this->user(), $this->shareResults), 'Заявка подана — статус в разделе «Заявки»');
    }

    public function withdraw(WithdrawApplication $withdraw): void
    {
        $application = $this->user()->applications()->where('internship_offer_id', $this->offerId)->firstOrFail();
        $this->perform(fn () => $withdraw($application, $this->user()), 'Заявка отозвана');
    }

    public function render(SkillMatcher $matcher): View
    {
        $offer = $this->offer();
        $application = $this->user()->applications()
            ->where('internship_offer_id', $offer->id)
            ->whereNot('status', 'withdrawn')
            ->first();

        return view('livewire.miniapp.offer-show', [
            'offer' => $offer,
            'match' => $matcher->match($offer, $this->user()),
            'application' => $application,
        ])->title($offer->title);
    }
}
