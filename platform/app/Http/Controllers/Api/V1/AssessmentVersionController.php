<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Assessments\CreateAssessmentVersion;
use App\Actions\Assessments\PublishAssessmentVersion;
use App\Actions\Assessments\SaveVersionQuestions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssessmentVersionRequest;
use App\Http\Resources\V1\AssessmentVersionResource;
use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Тесты
 */
class AssessmentVersionController extends Controller
{
    /** Версии теста. */
    public function index(Organization $organization, Assessment $assessment): AnonymousResourceCollection
    {
        Gate::authorize('view', $assessment);

        return AssessmentVersionResource::collection($assessment->versions()->withCount('questions')->get());
    }

    /**
     * Создать черновую версию.
     *
     * Если `questions` не переданы, копируются вопросы последней версии.
     */
    public function store(AssessmentVersionRequest $request, Organization $organization, Assessment $assessment, CreateAssessmentVersion $create): AssessmentVersionResource
    {
        Gate::authorize('update', $assessment);

        $version = $create($assessment, $request->validated('questions'), $request->validated('notes'));
        if ($scoring = $this->scoring($request)) {
            $version = app(SaveVersionQuestions::class)($version, $version->questions->map->only(
                ['skill_id', 'type', 'prompt', 'code', 'options', 'correct_keys', 'points'],
            )->all(), null, $scoring);
        }

        return AssessmentVersionResource::make($version->load('questions.version.assessment'));
    }

    /** Версия с вопросами. Правильные ответы видят только редакторы организации. */
    public function show(Organization $organization, Assessment $assessment, AssessmentVersion $version): AssessmentVersionResource
    {
        Gate::authorize('view', $assessment);

        return AssessmentVersionResource::make($version->load('questions.version.assessment'));
    }

    /**
     * Заменить вопросы черновой версии.
     *
     * Опубликованная версия не изменяется (ответ 422).
     */
    public function update(AssessmentVersionRequest $request, Organization $organization, Assessment $assessment, AssessmentVersion $version, SaveVersionQuestions $save): AssessmentVersionResource
    {
        Gate::authorize('update', $assessment);

        $version = $save($version, $request->validated('questions'), $request->validated('notes'), $this->scoring($request));

        return AssessmentVersionResource::make($version->load('questions.version.assessment'));
    }

    /** @return array<string, mixed> */
    private function scoring(AssessmentVersionRequest $request): array
    {
        return $request->safe()->only([
            'basic_threshold', 'min_questions_per_skill', 'applied_threshold', 'confident_threshold', 'practical_task', 'practical_rubric',
        ]);
    }

    /** Опубликовать версию. После публикации версия неизменяема. */
    public function publish(Organization $organization, Assessment $assessment, AssessmentVersion $version, PublishAssessmentVersion $publish): AssessmentVersionResource
    {
        Gate::authorize('update', $assessment);

        return AssessmentVersionResource::make($publish($version)->loadCount('questions'));
    }
}
