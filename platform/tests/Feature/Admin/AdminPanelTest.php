<?php

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Filament\Resources\Assessments\Pages\EditAssessment;
use App\Filament\Resources\Assessments\RelationManagers\VersionsRelationManager;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_opens_own_cabinet_but_not_foreign_one(): void
    {
        [$own, $foreign] = Organization::factory()->count(2)->create();
        $user = User::factory()->create();
        $user->organizations()->attach($own, ['role' => MemberRole::Editor]);

        $this->actingAs($user);
        $this->get("/admin/{$own->slug}/document-templates")->assertOk();
        $this->get("/admin/{$own->slug}/assessments")->assertOk();
        $this->get("/admin/{$foreign->slug}/assessments")->assertNotFound();
    }

    public function test_editor_publishes_version_through_shared_action(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization, ['role' => MemberRole::Editor]);
        $skill = Skill::create(['direction' => 'Frontend', 'title' => 'JavaScript']);

        $assessment = $organization->assessments()->create(['title' => 'Тест', 'direction' => 'Frontend']);
        $version = $assessment->versions()->create(['version' => 1]);
        $version->questions()->create([
            'skill_id' => $skill->id, 'type' => 'single', 'prompt' => 'Вопрос',
            'options' => [['key' => 'A', 'text' => 'Да'], ['key' => 'B', 'text' => 'Нет']], 'correct_keys' => ['A'],
        ]);

        $this->actingAs($user);
        Filament::setTenant($organization);

        Livewire::test(VersionsRelationManager::class, ['ownerRecord' => $assessment, 'pageClass' => EditAssessment::class])
            ->callAction(TestAction::make('publish')->table($version));

        $this->assertTrue($version->fresh()->isPublished());
    }

    public function test_editor_edits_draft_questions_and_new_version_copies_them(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization, ['role' => MemberRole::Editor]);
        $skill = Skill::create(['direction' => 'Frontend', 'title' => 'Git']);
        $assessment = $organization->assessments()->create(['title' => 'Тест', 'direction' => 'Frontend']);

        $this->actingAs($user);
        Filament::setTenant($organization);

        $component = Livewire::test(VersionsRelationManager::class, ['ownerRecord' => $assessment, 'pageClass' => EditAssessment::class])
            ->callAction(TestAction::make('newVersion')->table(), ['notes' => 'Черновик'])
            ->assertHasNoErrors();

        $version = $assessment->versions()->firstOrFail();

        $component
            ->mountAction(TestAction::make('questions')->table($version))
            ->setActionData([
                'questions' => [[
                    'skill_id' => $skill->id,
                    'type' => 'multiple',
                    'points' => 2,
                    'prompt' => 'Какие команды создают коммит?',
                    'code' => null,
                    'options' => [['key' => 'A', 'text' => 'git commit'], ['key' => 'B', 'text' => 'git push']],
                    'correct_keys' => ['A'],
                ]],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(['A'], $version->questions()->firstOrFail()->correct_keys);

        $component->callAction(TestAction::make('newVersion')->table(), ['notes' => 'v2']);
        $this->assertSame(1, $assessment->versions()->where('version', 2)->firstOrFail()->questions()->count());
    }
}
