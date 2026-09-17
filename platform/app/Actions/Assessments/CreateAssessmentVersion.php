<?php

namespace App\Actions\Assessments;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use Illuminate\Support\Facades\DB;

/**
 * Создаёт черновую версию теста. Без переданных вопросов копирует вопросы последней версии,
 * чтобы правка опубликованного теста начиналась с его текущего содержания.
 */
class CreateAssessmentVersion
{
    public function __construct(private SaveVersionQuestions $saveQuestions) {}

    /** @param list<array<string, mixed>>|null $questions */
    public function __invoke(Assessment $assessment, ?array $questions = null, ?string $notes = null): AssessmentVersion
    {
        return DB::transaction(function () use ($assessment, $questions, $notes) {
            Assessment::whereKey($assessment->id)->lockForUpdate()->first();

            $latest = $assessment->versions()->with('questions')->first();
            $version = $assessment->versions()->create([
                'version' => ($latest?->version ?? 0) + 1,
                'notes' => $notes,
            ]);

            $questions ??= $latest?->questions
                ->map(fn ($q) => $q->only(['skill_id', 'type', 'prompt', 'code', 'options', 'correct_keys', 'points']))
                ->all() ?? [];

            return $questions === [] ? $version : ($this->saveQuestions)($version, $questions);
        });
    }
}
