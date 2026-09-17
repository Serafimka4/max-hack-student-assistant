<?php

namespace App\Http\Requests\Api\V1;

use App\Actions\Assessments\QuestionRules;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class AssessmentVersionRequest extends FormRequest
{
    public function rules(): array
    {
        $organization = $this->route('organization');
        $rules = QuestionRules::for($organization instanceof Organization ? $organization : null);

        // При создании версии вопросы необязательны: тогда копируются из последней версии.
        if ($this->isMethod('POST')) {
            $rules['questions'] = ['sometimes', ...array_slice($rules['questions'], 1)];
        }

        return [
            'notes' => ['nullable', 'string', 'max:2000'],
            ...QuestionRules::scoring(),
            ...QuestionRules::practical($organization instanceof Organization ? $organization : null),
            ...$rules,
        ];
    }
}
