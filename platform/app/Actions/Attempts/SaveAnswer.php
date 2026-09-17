<?php

namespace App\Actions\Attempts;

use App\Enums\QuestionType;
use App\Exceptions\DomainRuleException;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/** Сохраняет ответ сразу при выборе: попытку можно продолжить после прерывания. */
class SaveAnswer
{
    public function __construct(private SubmitAttempt $submit) {}

    /** @param list<string> $keys */
    public function __invoke(AssessmentAttempt $attempt, User $user, int $questionId, array $keys): AttemptAnswer
    {
        if ($attempt->user_id !== $user->id) {
            throw new AuthorizationException;
        }
        if ($attempt->isSubmitted()) {
            throw new DomainRuleException('Попытка уже завершена.');
        }
        if ($attempt->isOverdue()) {
            ($this->submit)($attempt);
            throw new DomainRuleException('Время вышло — попытка завершена с сохранёнными ответами.');
        }

        $question = $attempt->version->questions()->findOrFail($questionId);
        $keys = array_values(array_unique(array_map('strval', $keys)));
        $allowed = array_column($question->options, 'key');

        if (array_diff($keys, $allowed) !== [] || ($question->type === QuestionType::Single && count($keys) > 1)) {
            throw ValidationException::withMessages(['answer' => 'Недопустимый вариант ответа.']);
        }

        return $attempt->answers()->updateOrCreate(
            ['assessment_question_id' => $question->id],
            ['selected_keys' => $keys],
        );
    }
}
