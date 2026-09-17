<?php

namespace Tests\Feature\MiniApp;

use App\Actions\Attempts\SaveAnswer;
use App\Actions\Attempts\StartAttempt;
use App\Actions\Attempts\SubmitAttempt;
use App\Actions\Practical\RequestPracticalAppeal;
use App\Actions\Practical\ReviewPractical;
use App\Actions\Practical\SubmitPractical;
use App\Enums\MemberRole;
use App\Enums\PracticalStatus;
use App\Enums\SkillLevel;
use App\Exceptions\DomainRuleException;
use App\Filament\Resources\PracticalSubmissions\Pages\ReviewPracticalSubmission;
use App\Livewire\MiniApp\AttemptResult;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use App\Support\Skills\SkillProfile;
use Filament\Facades\Filament;
use Livewire\Livewire;

class PracticalTest extends MiniAppTestCase
{
    private Assessment $assessment;

    private User $newcomer;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assessment = Assessment::with(['publishedVersion', 'organization'])->firstOrFail();
        $this->newcomer = User::factory()->create();
        $this->newcomer->student()->create(['organization_id' => $this->student->student->organization_id]);
        $this->reviewer = User::where('email', 'reviewer@demo.test')->firstOrFail();
    }

    private function finishedAttempt(bool $allCorrect = true): AssessmentAttempt
    {
        $attempt = app(StartAttempt::class)($this->assessment, $this->newcomer);
        foreach ($attempt->version->questions as $question) {
            $keys = $allCorrect ? $question->correct_keys : collect($question->options)->pluck('key')->diff($question->correct_keys)->take(1)->values()->all();
            app(SaveAnswer::class)($attempt, $this->newcomer, $question->id, $keys);
        }

        return app(SubmitAttempt::class)($attempt)->fresh();
    }

    public function test_practical_requires_finished_questions_and_some_content(): void
    {
        $open = app(StartAttempt::class)($this->assessment, $this->newcomer);

        try {
            app(SubmitPractical::class)($open, $this->newcomer, 'https://example.com/repo', null);
            $this->fail('До завершения вопросов отправка невозможна.');
        } catch (DomainRuleException) {
        }

        $attempt = app(SubmitAttempt::class)($open)->fresh();
        $this->expectException(DomainRuleException::class);
        app(SubmitPractical::class)($attempt, $this->newcomer, ' ', '');
    }

    public function test_review_raises_levels_only_where_questions_confirmed_basic_and_gives_junior(): void
    {
        $attempt = $this->finishedAttempt();
        $submission = app(SubmitPractical::class)($attempt, $this->newcomer, 'https://example.com/repo', null);
        $profile = app(SkillProfile::class);
        $this->assertSame('Стажёр', $profile->grade($attempt->fresh()));

        // Рубрика: HTML 4+2, JS 4, HTTP 4, Git 2. Всё на максимум, кроме Git (1 из 2 = 50%).
        app(ReviewPractical::class)($submission, $this->reviewer, [4, 2, 4, 4, 1], 'Отлично');

        $levels = $attempt->skillResults()->with('skill')->get()->mapWithKeys(fn ($r) => [$r->skill->title => $r->level]);
        $this->assertSame(SkillLevel::Confident, $levels['HTML / CSS']);
        $this->assertSame(SkillLevel::Confident, $levels['JavaScript']);
        $this->assertSame(SkillLevel::Basic, $levels['Git']);
        $this->assertSame('Стажёр', $profile->grade($attempt->fresh()));

        // Пересмотр: Git 2 из 2 — все навыки не ниже прикладного.
        app(RequestPracticalAppeal::class)($submission->fresh(), $this->newcomer, 'Коммиты оформлены по соглашению команды');
        app(ReviewPractical::class)($submission->fresh(), $this->reviewer, [4, 2, 4, 4, 2], 'Пересмотрено');

        $this->assertSame('Junior — предварительно', $profile->grade($attempt->fresh()));
        $this->assertSame(2, $submission->reviews()->count());
        $this->assertTrue($submission->reviews()->first()->is_appeal);
    }

    public function test_practical_does_not_lift_skill_not_confirmed_by_questions(): void
    {
        $attempt = $this->finishedAttempt(allCorrect: false);
        $submission = app(SubmitPractical::class)($attempt, $this->newcomer, 'https://example.com/repo', null);

        app(ReviewPractical::class)($submission, $this->reviewer, [4, 2, 4, 4, 2], null);

        $this->assertTrue($attempt->skillResults()->get()->every(fn ($r) => $r->level === null && $r->practical_points === $r->practical_max_points));
        $this->assertNull(app(SkillProfile::class)->grade($attempt->fresh()));
    }

    public function test_review_validation_and_appeal_rules(): void
    {
        $attempt = $this->finishedAttempt();
        $submission = app(SubmitPractical::class)($attempt, $this->newcomer, 'https://example.com/repo', null);

        foreach ([[4, 2, 4], [5, 2, 4, 4, 2]] as $invalid) {
            try {
                app(ReviewPractical::class)($submission, $this->reviewer, $invalid, null);
                $this->fail('Некорректные баллы должны отклоняться.');
            } catch (DomainRuleException) {
            }
        }

        try {
            app(RequestPracticalAppeal::class)($submission, $this->newcomer, 'Ещё не проверено');
            $this->fail('Пересмотр до проверки недоступен.');
        } catch (DomainRuleException) {
        }

        app(ReviewPractical::class)($submission, $this->reviewer, [1, 1, 1, 1, 1], null);
        app(RequestPracticalAppeal::class)($submission->fresh(), $this->newcomer, 'Прошу пересмотреть');
        app(ReviewPractical::class)($submission->fresh(), $this->reviewer, [2, 1, 1, 1, 1], null);

        $this->expectException(DomainRuleException::class);
        app(RequestPracticalAppeal::class)($submission->fresh(), $this->newcomer, 'И ещё раз');
    }

    public function test_student_submits_solution_from_result_screen(): void
    {
        $attempt = $this->finishedAttempt();
        $this->actingAs($this->newcomer);

        Livewire::test(AttemptResult::class, ['attempt' => $attempt])
            ->assertSee('Практическое задание')
            ->set('link', 'http://insecure.example')
            ->call('submitPractical')
            ->assertHasErrors('link')
            ->set('link', 'https://github.com/demo/vacancies')
            ->call('submitPractical')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Решение отправлено на проверку');

        $this->assertSame(PracticalStatus::Pending, $attempt->practical()->sole()->status);
    }

    public function test_only_reviewers_of_test_organization_review_in_admin(): void
    {
        $submission = app(SubmitPractical::class)($this->finishedAttempt(), $this->newcomer, 'https://example.com/repo', null);
        $organization = $this->assessment->organization;

        $editor = User::factory()->create();
        $editor->organizations()->attach($organization, ['role' => MemberRole::Editor]);
        $this->actingAs($editor);
        $this->get("/admin/{$organization->slug}/practical-submissions")->assertForbidden();

        $this->flushSession();
        $this->actingAs($this->reviewer);
        $this->get("/admin/{$organization->slug}/practical-submissions")->assertOk()->assertSee('№ '.$submission->assessment_attempt_id);
        $this->get("/admin/{$organization->slug}/practical-submissions/{$submission->id}/review")
            ->assertOk()
            ->assertDontSee($this->newcomer->name);

        Filament::setTenant($organization);
        Livewire::test(ReviewPracticalSubmission::class, ['record' => $submission->getRouteKey()])
            ->fillForm(['scores' => [3, 2, 3, 3, 1], 'comment' => 'Хорошо'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(PracticalStatus::Reviewed, $submission->fresh()->status);
        $this->assertSame([3, 2, 3, 3, 1], $submission->latestReview()->first()->scores);
    }
}
