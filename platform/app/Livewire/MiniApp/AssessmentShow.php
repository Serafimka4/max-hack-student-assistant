<?php

namespace App\Livewire\MiniApp;

use App\Actions\Attempts\StartAttempt;
use App\Exceptions\DomainRuleException;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.miniapp')]
class AssessmentShow extends Component
{
    use InteractsWithStudent;

    #[Locked]
    public int $assessmentId;

    public function mount(Assessment $assessment): void
    {
        abort_unless($assessment->publishedVersion()->exists(), 404);
        $this->assessmentId = $assessment->id;
    }

    public function start(StartAttempt $start): void
    {
        try {
            $attempt = $start(Assessment::findOrFail($this->assessmentId), $this->user());
        } catch (DomainRuleException $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $this->redirectRoute('miniapp.attempts.take', $attempt, navigate: true);
    }

    public function render(): View
    {
        $assessment = Assessment::with(['organization', 'publishedVersion.questions.skill'])->findOrFail($this->assessmentId);

        $attempts = AssessmentAttempt::where('user_id', $this->user()->id)
            ->whereHas('version', fn ($q) => $q->where('assessment_id', $assessment->id))
            ->with('version')
            ->latest('id')
            ->get();

        $lastSubmitted = $attempts->whereNotNull('submitted_at')->sortByDesc('submitted_at')->first();
        $retakeAt = $lastSubmitted?->submitted_at->addDays($assessment->retake_after_days);

        return view('livewire.miniapp.assessment-show', [
            'assessment' => $assessment,
            'version' => $assessment->publishedVersion,
            'skills' => $assessment->publishedVersion->questions->groupBy('skill_id')
                ->map(fn ($questions) => ['skill' => $questions->first()->skill, 'count' => $questions->count()]),
            'open' => $attempts->first(fn ($a) => ! $a->isSubmitted() && ! $a->isOverdue()),
            'lastSubmitted' => $lastSubmitted,
            'retakeAt' => $retakeAt?->isFuture() ? $retakeAt : null,
        ])->title($assessment->title);
    }
}
