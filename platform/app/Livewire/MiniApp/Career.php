<?php

namespace App\Livewire\MiniApp;

use App\Enums\OfferDirection;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Support\Skills\SkillMatcher;
use App\Support\Skills\SkillProfile;
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

    public function render(SkillMatcher $matcher, SkillProfile $profile): View
    {
        $this->view = in_array($this->view, ['offers', 'skills', 'applications'], true) ? $this->view : 'offers';
        $direction = OfferDirection::tryFrom($this->direction);
        $student = $this->student();
        $user = $this->user();

        $data = ['directions' => OfferDirection::cases(), 'activeDirection' => $direction];
        $results = $profile->latestResults($user);

        if ($this->view === 'offers') {
            $offers = $student->organization->internshipOffers()->published()
                ->when($direction, fn ($q) => $q->where('direction', $direction))
                ->with('skills')
                ->orderBy('apply_until')
                ->get();

            $data['offers'] = $offers->map(fn ($offer) => [
                'offer' => $offer,
                'match' => $matcher->match($offer, $user, $results),
            ]);
            $data['appliedOfferIds'] = $user->applications()->whereNot('status', 'withdrawn')->pluck('internship_offer_id')->all();
        }

        if ($this->view === 'skills') {
            $attempts = AssessmentAttempt::where('user_id', $user->id)
                ->with(['version', 'skillResults'])
                ->latest('id')
                ->get();

            $data['results'] = $results->sortBy('skill.title');
            $data['assessments'] = Assessment::whereHas('publishedVersion')
                ->with(['organization', 'publishedVersion.questions'])
                ->orderBy('title')
                ->get()
                ->map(function (Assessment $assessment) use ($attempts, $profile) {
                    $mine = $attempts->filter(fn ($a) => $a->version->assessment_id === $assessment->id);
                    $last = $mine->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first();

                    return [
                        'assessment' => $assessment,
                        'open' => $mine->first(fn ($a) => ! $a->isSubmitted() && ! $a->isOverdue()),
                        'last' => $last,
                        'grade' => $last ? $profile->grade($last) : null,
                    ];
                });
        }

        if ($this->view === 'applications') {
            $data['applications'] = $user->applications()->with(['offer', 'history'])->latest('updated_at')->get();
        }

        return view('livewire.miniapp.career', $data);
    }
}
