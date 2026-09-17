<?php

namespace App\Actions\Assessments;

use App\Enums\QuestionType;
use App\Exceptions\DomainRuleException;
use App\Models\AssessmentVersion;
use Illuminate\Support\Facades\DB;

/**
 * Полностью заменяет вопросы черновой версии. Опубликованную версию менять нельзя.
 */
class SaveVersionQuestions
{
    /**
     * @param  list<array<string, mixed>>  $questions  уже прошедшие QuestionRules
     * @param  array{basic_threshold?: int, min_questions_per_skill?: int}  $scoring  правила оценки версии
     */
    public function __invoke(AssessmentVersion $version, array $questions, ?string $notes = null, array $scoring = []): AssessmentVersion
    {
        if ($version->isPublished()) {
            throw new DomainRuleException('Опубликованная версия теста не изменяется. Создайте новую версию.');
        }

        foreach (array_values($questions) as $i => $question) {
            $this->assertConsistent($question, $i + 1);
        }

        return DB::transaction(function () use ($version, $questions, $notes) {
            $version->questions()->delete();

            foreach (array_values($questions) as $i => $q) {
                $version->questions()->create([
                    'skill_id' => $q['skill_id'],
                    'type' => $q['type'],
                    'prompt' => $q['prompt'],
                    'code' => $q['code'] ?? null,
                    'options' => array_values(array_map(
                        fn (array $o) => ['key' => (string) $o['key'], 'text' => $o['text']],
                        $q['options'],
                    )),
                    'correct_keys' => array_values(array_map('strval', $q['correct_keys'])),
                    'points' => $q['points'] ?? 1,
                    'position' => $i + 1,
                ]);
            }

            $version->update(array_filter([
                'notes' => $notes,
                'basic_threshold' => $scoring['basic_threshold'] ?? null,
                'min_questions_per_skill' => $scoring['min_questions_per_skill'] ?? null,
            ], fn ($value) => $value !== null));

            return $version->load('questions');
        });
    }

    /** @param array<string, mixed> $q */
    private function assertConsistent(array $q, int $number): void
    {
        $keys = array_map(fn (array $o) => (string) $o['key'], $q['options']);
        $correct = array_map('strval', $q['correct_keys']);

        if (array_diff($correct, $keys) !== []) {
            throw new DomainRuleException("Вопрос {$number}: правильный ответ не совпадает ни с одним вариантом.");
        }

        $type = $q['type'] instanceof QuestionType ? $q['type'] : QuestionType::from($q['type']);

        if ($type === QuestionType::Single && count($correct) !== 1) {
            throw new DomainRuleException("Вопрос {$number}: для типа «один ответ» нужен ровно один правильный вариант.");
        }
    }
}
