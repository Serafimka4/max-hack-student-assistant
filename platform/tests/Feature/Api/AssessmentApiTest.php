<?php

namespace Tests\Feature\Api;

use App\Enums\MemberRole;
use App\Models\Organization;

class AssessmentApiTest extends ApiTestCase
{
    private function createAssessment(): int
    {
        return $this->postJson($this->url('/assessments'), [
            'title' => 'Диагностика frontend', 'direction' => 'Frontend-разработка',
        ])->assertCreated()->json('data.id');
    }

    public function test_full_version_lifecycle(): void
    {
        $this->actingAsMember();
        $skill = $this->skill();
        $id = $this->createAssessment();

        $versionId = $this->postJson($this->url("/assessments/{$id}/versions"), [
            'notes' => 'Первая версия',
            'questions' => [$this->question($skill)],
        ])->assertCreated()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.is_published', false)
            ->assertJsonPath('data.questions.0.correct_keys', ['A'])
            ->json('data.id');

        $this->postJson($this->url("/assessments/{$id}/versions/{$versionId}/publish"))
            ->assertOk()->assertJsonPath('data.is_published', true);

        $this->putJson($this->url("/assessments/{$id}/versions/{$versionId}"), ['questions' => [$this->question($skill)]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Опубликованная версия теста не изменяется. Создайте новую версию.');

        // Новая версия без вопросов копирует вопросы опубликованной.
        $this->postJson($this->url("/assessments/{$id}/versions"), [])
            ->assertCreated()
            ->assertJsonPath('data.version', 2)
            ->assertJsonCount(1, 'data.questions');

        $this->getJson($this->url("/assessments/{$id}"))->assertJsonPath('data.published_version.version', 1);
    }

    public function test_answer_keys_are_hidden_from_non_editors(): void
    {
        $this->actingAsMember();
        $id = $this->createAssessment();
        $versionId = $this->postJson($this->url("/assessments/{$id}/versions"), ['questions' => [$this->question($this->skill())]])
            ->json('data.id');

        $this->actingAsMember(MemberRole::Reviewer);
        $response = $this->getJson($this->url("/assessments/{$id}/versions/{$versionId}"))->assertOk();

        $this->assertArrayNotHasKey('correct_keys', $response->json('data.questions.0'));
    }

    public function test_validates_question_consistency(): void
    {
        $this->actingAsMember();
        $skill = $this->skill();
        $id = $this->createAssessment();

        $this->postJson($this->url("/assessments/{$id}/versions"), [
            'questions' => [$this->question($skill, ['correct_keys' => ['A', 'B']])],
        ])->assertUnprocessable()->assertJsonPath('message', 'Вопрос 1: для типа «один ответ» нужен ровно один правильный вариант.');

        $this->postJson($this->url("/assessments/{$id}/versions"), [
            'questions' => [$this->question($skill, ['correct_keys' => ['Z']])],
        ])->assertUnprocessable();

        $this->postJson($this->url("/assessments/{$id}/versions"), [
            'questions' => [$this->question($this->skill(Organization::factory()->create()))],
        ])->assertUnprocessable()->assertJsonValidationErrors('questions.0.skill_id');
    }

    public function test_cannot_publish_empty_version(): void
    {
        $this->actingAsMember();
        $id = $this->createAssessment();
        $versionId = $this->postJson($this->url("/assessments/{$id}/versions"), [])->assertCreated()->json('data.id');

        $this->postJson($this->url("/assessments/{$id}/versions/{$versionId}/publish"))->assertUnprocessable();
    }

    public function test_outsider_cannot_read_or_create_assessments(): void
    {
        $this->actingAsOutsider();

        $this->getJson($this->url('/assessments'))->assertForbidden();
        $this->postJson($this->url('/assessments'), ['title' => 'X', 'direction' => 'Y'])->assertForbidden();
    }

    public function test_openapi_document_is_generated(): void
    {
        $this->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonStructure(['paths' => ['/v1/organizations/{organization}/assessments/{assessment}/versions/{version}/publish']]);
    }
}
