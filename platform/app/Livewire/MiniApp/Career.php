<?php

namespace App\Livewire\MiniApp;

use App\Enums\OfferDirection;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\Assessment;
use App\Support\Skills\SkillMatcher;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Карьера')]
class Career extends Component
{
    use InteractsWithStudent;

    #[Url]
    public string $view = 'offers';

    #[Url(except: '')]
    public string $direction = '';

    public function render(SkillMatcher $matcher): View
    {
        $this->view = in_array($this->view, ['offers', 'skills', 'applications'], true) ? $this->view : 'offers';
        $direction = OfferDirection::tryFrom($this->direction);
        $student = $this->student();
        $user = $this->user();

        $data = ['directions' => OfferDirection::cases(), 'activeDirection' => $direction];

        if ($this->view === 'offers') {
            $offers = $student->organization->internshipOffers()->published()
                ->when($direction, fn ($q) => $q->where('direction', $direction))
                ->with('skills')
                ->orderBy('apply_until')
                ->get();

            $data['offers'] = $offers->map(fn ($offer) => [
                'offer' => $offer,
                'match' => $matcher->match($offer, $user),
            ]);
            $data['appliedOfferIds'] = $user->applications()->whereNot('status', 'withdrawn')->pluck('internship_offer_id')->all();
        }

        if ($this->view === 'skills') {
            $data['assessments'] = Assessment::whereHas('publishedVersion')
                ->with(['organization', 'publishedVersion.questions.skill'])
                ->orderBy('title')
                ->get();
        }

        if ($this->view === 'applications') {
            $data['applications'] = $user->applications()->with(['offer', 'history'])->latest('updated_at')->get();
        }

        return view('livewire.miniapp.career', $data);
    }
}
