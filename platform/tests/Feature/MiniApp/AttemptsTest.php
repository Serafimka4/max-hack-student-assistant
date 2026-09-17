<?php

namespace Tests\Feature\MiniApp;

use App\Actions\Attempts\SaveAnswer;
use App\Actions\Attempts\StartAttempt;
use App\Actions\Attempts\SubmitAttempt;
use App\Enums\SkillLevel;
use App\Enums\SkillOutcome;
use App\Exceptions\DomainRuleException;
use App\Livewire\MiniApp\AssessmentShow;
use App\Livewire\MiniApp\AttemptTake;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\InternshipOffer;
use App\Models\User;
use App\Support\Skills\SkillMatcher;
use App\Support\Skills\SkillProfile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

class AttemptsTest extends MiniAppTestCase
{
    private Assessment $assessment;

    private User $newcomer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessment = Assessment::with('publishedVersion')->firstOrFail();
        $this->newcomer = User::factory()->create();
        $this->newcomer->student()->create(['organization_id' => $this->student->student->organization_id]);
    }

    /** Отвечает на все вопросы: правильно по перечисленным навыкам, неправильно по остальным. */
    private function answer(AssessmentAttempt $attempt, User $user, array $correctSkills): void
    {
        foreach ($attempt->version->questions()->with('skill')->get() as $question) {
            $right = in_array($question->skill->title, $correctSkills, true);
            $wrong = collect($question->options)->pluck('key')->diff($question->correct_keys)->take(1)->values()->all();
            app(SaveAnswer::class)($attempt, $user, $question->id, $right ? $question->correct_keys : $wrong);
        }
    }

    public function test_scoring_levels_and_grade(): void
    {
        $attempt = app(StartAttempt::class)($this->assessment, $this->newcomer);
        $this->answer($attempt, $this->newcomer, ['HTML / CSS', 'JavaScript', 'HTTP и API', 'Git']);
        app(SubmitAttempt::class)($attempt);

        $results = app(SkillProfile::class)->latestResults($this->newcomer);
        $this->assertCount(4, $results);
        $this->assertTrue($results->every(fn ($r) => $r->outcome === SkillOutcome::Achieved && $r->level === SkillLevel::Basic));
        $this->assertSame('Стажёр', app(SkillProfile::class)->grade($attempt->fresh()));
    }

    public function test_partial_answer_scores_zero_and_insufficient_data_is_not_low_score(): void
    {
        $version = $this->assessment->publishedVersion;
        $version->forceFill(['min_questions_per_skill' => 3])->save();

        $attempt = app(StartAttempt::class)($this->assessment, $this->newcomer);
        $multiple = $version->questions()->where('type', 'multiple')->firstOrFail();
        app(SaveAnswer::class)($attempt, $this->newcomer, $multiple->id, [$multiple->correct_keys[0]]);
        app(SubmitAttempt::class)($attempt);

        $this->assertSame(0, $attempt->answers()->first()->points_awarded);
        $results = $attempt->skillResults()->get();
        $this->assertTrue($results->every(fn ($r) => $r->outcome === SkillOutcome::InsufficientData && $r->level === null));
        $this->assertNull(app(SkillProfile::class)->grade($attempt->fresh()));
    }

    public function test_resume_open_attempt_and_retake_rule(): void
    {
        $first = app(StartAttempt::class)($this->assessment, $this->newcomer);
        $this->assertTrue($first->is(app(StartAttempt::class)($this->assessment, $this->newcomer)));

        app(SubmitAttempt::class)($first);
        app(SubmitAttempt::class)($first); // повторное завершение не создаёт дублей результатов
        $this->assertSame(4, $first->skillResults()->count());

        try {
            app(StartAttempt::class)($this->assessment, $this->newcomer);
            $this->fail('Повторная попытка до срока должна быть запрещена.');
        } catch (DomainRuleException $e) {
            $this->assertStringContainsString('Повторная попытка будет доступна', $e->getMessage());
        }

        $this->travel($this->assessment->retake_after_days + 1)->days();
        $this->assertFalse($first->is(app(StartAttempt::class)($this->assessment, $this->newcomer)));
    }

    public function test_answers_are_validated_and_time_limit_finishes_attempt(): void
    {
        $attempt = app(StartAttempt::class)($this->assessment, $this->newcomer);
        $single = $attempt->version->questions()->where('type', 'single')->firstOrFail();

        try {
            app(SaveAnswer::class)($attempt, $this->newcomer, $single->id, ['A', 'B']);
            $this->fail('Два варианта в вопросе с одним ответом недопустимы.');
        } catch (ValidationException) {
        }

        $this->travel($this->assessment->duration_minutes + 1)->minutes();
        $this->expectException(DomainRuleException::class);
        try {
            app(SaveAnswer::class)($attempt->fresh(), $this->newcomer, $single->id, [$single->correct_keys[0]]);
        } finally {
            $this->assertTrue($attempt->fresh()->isSubmitted());
            $this->assertTrue($attempt->fresh()->expired);
        }
    }

    public function test_student_takes_test_in_miniapp_without_seeing_answer_keys(): void
    {
        $this->actingAs($this->newcomer);

        Livewire::test(AssessmentShow::class, ['assessment' => $this->assessment])->call('start')->assertRedirect();
        $attempt = AssessmentAttempt::where('user_id', $this->newcomer->id)->sole();
        $question = $attempt->version->questions->first();

        $component = Livewire::test(AttemptTake::class, ['attempt' => $attempt])
            ->assertSee($question->prompt)
            ->call('choose', $question->id, $question->correct_keys[0]);

        $this->assertStringNotContainsString('correct_keys', json_encode($component->snapshot));
        $this->assertSame([$question->correct_keys[0]], $attempt->answers()->sole()->selected_keys);

        $component->call('finish')->assertRedirect(route('miniapp.attempts.result', $attempt));
        $this->get(route('miniapp.attempts.result', $attempt))->assertOk()->assertSee('По навыкам');
    }

    public function test_other_students_attempt_is_not_accessible(): void
    {
        $attempt = app(StartAttempt::class)($this->assessment, $this->newcomer);
        $this->actingAs($this->student);

        $this->get(route('miniapp.attempts.take', $attempt))->assertNotFound();
        $this->expectException(AuthorizationException::class);
        app(SaveAnswer::class)($attempt, $this->student, $attempt->version->questions->first()->id, ['A']);
    }

    public function test_offer_match_uses_latest_results(): void
    {
        $offer = InternshipOffer::with('skills')->where('title', 'Frontend-стажёр')->firstOrFail();
        $match = app(SkillMatcher::class)->match($offer, $this->student)->keyBy(fn ($row) => $row['skill']->title);

        // Демо-профиль: HTML/CSS базовый при требуемом прикладном, HTTP подтверждён, JS и Git ниже базового.
        $this->assertSame('gap', $match['HTML / CSS']['state']);
        $this->assertSame('ok', $match['HTTP и API']['state']);
        $this->assertSame('gap', $match['JavaScript']['state']);
        $this->assertSame('gap', $match['Git']['state']);
    }
}
