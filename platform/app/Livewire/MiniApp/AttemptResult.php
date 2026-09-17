<?php

namespace App\Livewire\MiniApp;

use App\Actions\Practical\RequestPracticalAppeal;
use App\Actions\Practical\SubmitPractical;
use App\Enums\SkillOutcome;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\AssessmentAttempt;
use App\Support\Skills\SkillProfile;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Результат диагностики')]
class AttemptResult extends Component
{
    use InteractsWithStudent;

    #[Locked]
    public int $attemptId;

    #[Validate('nullable|url:https|max:500', as: 'ссылка')]
    public string $link = '';

    #[Validate('nullable|string|max:10000', as: 'описание')]
    public string $answer = '';

    #[Validate('required|string|min:10|max:2000', as: 'причина', onUpdate: false)]
    public string $appealReason = '';

    public function mount(AssessmentAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $this->user()->id, 404);
        abort_unless($attempt->isSubmitted(), 404);
        $this->attemptId = $attempt->id;
    }

    private function attempt(): AssessmentAttempt
    {
        return AssessmentAttempt::with(['version.assessment', 'skillResults.skill', 'practical.latestReview'])->findOrFail($this->attemptId);
    }

    public function submitPractical(SubmitPractical $submit): void
    {
        $this->validateOnly('link');
        $this->validateOnly('answer');
        $this->perform(fn () => $submit($this->attempt(), $this->user(), $this->link, $this->answer), 'Решение отправлено на проверку');
    }

    public function requestAppeal(RequestPracticalAppeal $appeal): void
    {
        $this->validateOnly('appealReason');
        $submission = $this->attempt()->practical ?? abort(404);
        $this->perform(fn () => $appeal($submission, $this->user(), $this->appealReason), 'Запрос на пересмотр отправлен');
        $this->reset('appealReason');
    }

    public function render(SkillProfile $profile): View
    {
        $attempt = $this->attempt();
        if ($attempt->practical && blank($this->link) && blank($this->answer)) {
            $this->link = (string) $attempt->practical->link;
            $this->answer = (string) $attempt->practical->answer;
        }

        return view('livewire.miniapp.attempt-result', [
            'attempt' => $attempt,
            'assessment' => $attempt->version->assessment,
            'grade' => $profile->grade($attempt),
            'results' => $attempt->skillResults->sortBy('skill.title'),
            'toImprove' => $attempt->skillResults->where('outcome', '!=', SkillOutcome::Achieved)->sortBy('skill.title'),
            'practical' => $attempt->practical?->load('reviews'),
        ]);
    }
}
