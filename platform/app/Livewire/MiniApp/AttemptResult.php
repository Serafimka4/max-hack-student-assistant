<?php

namespace App\Livewire\MiniApp;

use App\Enums\SkillOutcome;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\AssessmentAttempt;
use App\Support\Skills\SkillProfile;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Результат диагностики')]
class AttemptResult extends Component
{
    use InteractsWithStudent;

    #[Locked]
    public int $attemptId;

    public function mount(AssessmentAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $this->user()->id, 404);
        abort_unless($attempt->isSubmitted(), 404);
        $this->attemptId = $attempt->id;
    }

    public function render(SkillProfile $profile): View
    {
        $attempt = AssessmentAttempt::with(['version.assessment', 'skillResults.skill'])->findOrFail($this->attemptId);

        return view('livewire.miniapp.attempt-result', [
            'attempt' => $attempt,
            'assessment' => $attempt->version->assessment,
            'grade' => $profile->grade($attempt),
            'results' => $attempt->skillResults->sortBy('skill.title'),
            'toImprove' => $attempt->skillResults->where('outcome', '!=', SkillOutcome::Achieved)->sortBy('skill.title'),
        ]);
    }
}
