<?php

namespace App\Actions\Assessments;

use App\Enums\QuestionType;
use App\Models\Organization;
use App\Models\Skill;
use Illuminate\Validation\Rule;

/** Правила вопросов теста — общие для API и админ-панели. */
class QuestionRules
{
    /** @return array<string, mixed> правила оценки версии */
    public static function scoring(): array
    {
        return [
            /** Порог базового уровня по навыку, %. */
            'basic_threshold' => ['sometimes', 'integer', 'min:1', 'max:100'],
            /** Минимум вопросов на навык, иначе «недостаточно данных». */
            'min_questions_per_skill' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }

    /** @return array<string, mixed> */
    /** Организация может отсутствовать только при построении документации API: тогда доступны лишь общие навыки. */
    public static function for(?Organization $organization, string $prefix = 'questions'): array
    {
        return [
            $prefix => ['required', 'array', 'min:1', 'max:100'],
            "{$prefix}.*.skill_id" => ['required', 'integer', Rule::exists(Skill::class, 'id')->where(
                fn ($q) => $q->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $organization?->id)),
            )],
            "{$prefix}.*.type" => ['required', Rule::enum(QuestionType::class)],
            "{$prefix}.*.prompt" => ['required', 'string', 'max:5000'],
            "{$prefix}.*.code" => ['nullable', 'string', 'max:10000'],
            "{$prefix}.*.points" => ['nullable', 'integer', 'min:1', 'max:100'],
            "{$prefix}.*.options" => ['required', 'array', 'min:2', 'max:10'],
            "{$prefix}.*.options.*.key" => ['required', 'string', 'max:16', 'distinct'],
            "{$prefix}.*.options.*.text" => ['required', 'string', 'max:2000'],
            "{$prefix}.*.correct_keys" => ['required', 'array', 'min:1'],
            "{$prefix}.*.correct_keys.*" => ['string', 'distinct'],
        ];
    }
}
