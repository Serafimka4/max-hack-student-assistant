<?php

namespace App\Livewire\MiniApp;

use App\Actions\Attempts\SaveAnswer;
use App\Actions\Attempts\SubmitAttempt;
use App\Enums\QuestionType;
use App\Exceptions\DomainRuleException;
use App\Livewire\MiniApp\Concerns\InteractsWithStudent;
use App\Models\AssessmentAttempt;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.miniapp')]
#[Title('Диагностика')]
class AttemptTake extends Component
{
    use InteractsWithStudent;

    #[Locked]
    public int $attemptId;

    #[Url(as: 'q')]
    public int $index = 0;

    /** @var array<int, list<string>> выбранные варианты по id вопроса (без правильных ответов) */
    #[Locked]
    public array $answers = [];

    public function mount(AssessmentAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $this->user()->id, 404);
        $this->attemptId = $attempt->id;

        if ($attempt->isSubmitted() || $attempt->isOverdue()) {
            $this->finish(app(SubmitAttempt::class));

            return;
        }

        $this->answers = $attempt->answers->mapWithKeys(fn ($a) => [$a->assessment_question_id => $a->selected_keys])->all();
    }

    private function attempt(): AssessmentAttempt
    {
        return AssessmentAttempt::with('version.assessment', 'version.questions')->findOrFail($this->attemptId);
    }

    public function choose(int $questionId, string $key, SaveAnswer $save): void
    {
        $attempt = $this->attempt();
        $question = $attempt->version->questions->firstWhere('id', $questionId) ?? abort(404);
        $current = $this->answers[$questionId] ?? [];

        $keys = $question->type === QuestionType::Single
            ? [$key]
            : (in_array($key, $current, true) ? array_values(array_diff($current, [$key])) : [...$current, $key]);

        try {
            $save($attempt, $this->user(), $questionId, $keys);
            $this->answers[$questionId] = $keys;
        } catch (DomainRuleException $e) {
            $this->toast($e->getMessage(), 'error');
            $this->redirectRoute('miniapp.attempts.result', $attempt, navigate: true);
        }
    }

    public function go(int $index): void
    {
        $this->index = max(0, min($index, $this->attempt()->version->questions->count() - 1));
    }

    public function finish(SubmitAttempt $submit): void
    {
        $attempt = $submit($this->attempt());
        $this->redirectRoute('miniapp.attempts.result', $attempt, navigate: true);
    }

    public function render(): View
    {
        $attempt = $this->attempt();
        $questions = $attempt->version->questions;
        $this->index = max(0, min($this->index, $questions->count() - 1));

        return view('livewire.miniapp.attempt-take', [
            'attempt' => $attempt,
            'questions' => $questions,
            'question' => $questions[$this->index],
            'answered' => collect($this->answers)->filter()->count(),
        ]);
    }
}
