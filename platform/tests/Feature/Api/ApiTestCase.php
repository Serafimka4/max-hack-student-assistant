<?php

namespace Tests\Feature\Api;

use App\Enums\MemberRole;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    protected function actingAsMember(MemberRole $role = MemberRole::Editor, ?Organization $organization = null): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($organization ?? $this->organization, ['role' => $role]);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsOutsider(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function url(string $path = ''): string
    {
        return "/api/v1/organizations/{$this->organization->slug}".$path;
    }

    protected function skill(?Organization $organization = null): Skill
    {
        return Skill::create(['organization_id' => $organization?->id, 'direction' => 'Frontend', 'title' => 'JavaScript '.uniqid()]);
    }

    /** @return array<string, mixed> */
    protected function question(Skill $skill, array $overrides = []): array
    {
        return array_merge([
            'skill_id' => $skill->id,
            'type' => 'single',
            'prompt' => 'Что выведет код?',
            'code' => 'console.log(1 + 1)',
            'options' => [['key' => 'A', 'text' => '2'], ['key' => 'B', 'text' => '11']],
            'correct_keys' => ['A'],
            'points' => 1,
        ], $overrides);
    }
}
