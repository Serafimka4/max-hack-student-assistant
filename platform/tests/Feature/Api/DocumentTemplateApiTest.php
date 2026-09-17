<?php

namespace Tests\Feature\Api;

use App\Actions\Templates\StoreTemplateVersion;
use App\Enums\MemberRole;
use App\Models\DocumentTemplate;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentTemplateApiTest extends ApiTestCase
{
    public function test_editor_creates_template_and_uploads_versions(): void
    {
        Storage::fake('local');
        $this->actingAsMember(MemberRole::Editor);

        $id = $this->postJson($this->url('/document-templates'), ['title' => 'Заявление на практику', 'is_published' => true])
            ->assertCreated()->json('data.id');

        foreach ([1, 2] as $expected) {
            $this->post($this->url("/document-templates/{$id}/versions"), [
                'file' => UploadedFile::fake()->create('zayavlenie.pdf', 120, 'application/pdf'),
            ], ['Accept' => 'application/json'])
                ->assertCreated()
                ->assertJsonPath('data.version', $expected);
        }

        $response = $this->getJson($this->url("/document-templates/{$id}"))
            ->assertOk()
            ->assertJsonPath('data.current_version.version', 2);

        $this->get($response->json('data.current_version.download_url'))->assertOk()->assertDownload('zayavlenie.pdf');
    }

    public function test_download_link_requires_valid_unexpired_signature(): void
    {
        Storage::fake('local');
        $template = $this->organization->documentTemplates()->create(['title' => 'Шаблон']);
        $version = app(StoreTemplateVersion::class)(
            $template, UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), null,
        );

        $this->get("/files/templates/{$version->id}")->assertForbidden();
        $this->get($version->temporaryDownloadUrl().'x')->assertForbidden();

        $url = $version->temporaryDownloadUrl(1);
        $this->travel(2)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_rejects_non_document_files(): void
    {
        $this->actingAsMember();
        $template = $this->organization->documentTemplates()->create(['title' => 'Шаблон']);

        $this->post($this->url("/document-templates/{$template->id}/versions"), [
            'file' => UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_reviewer_and_outsider_cannot_manage_templates(): void
    {
        $this->actingAsMember(MemberRole::Reviewer);
        $this->postJson($this->url('/document-templates'), ['title' => 'X'])->assertForbidden();

        $this->actingAsOutsider();
        $this->postJson($this->url('/document-templates'), ['title' => 'X'])->assertForbidden();
    }

    public function test_outsider_sees_only_published_templates(): void
    {
        $this->organization->documentTemplates()->create(['title' => 'Опубликован', 'is_published' => true]);
        $draft = $this->organization->documentTemplates()->create(['title' => 'Черновик']);

        $this->actingAsOutsider();
        $this->getJson($this->url('/document-templates'))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Опубликован');
        $this->getJson($this->url("/document-templates/{$draft->id}"))->assertForbidden();
    }

    public function test_template_of_another_organization_is_not_reachable_through_this_organization(): void
    {
        $other = Organization::factory()->create();
        $foreign = $other->documentTemplates()->create(['title' => 'Чужой', 'is_published' => true]);
        $this->actingAsMember();

        $this->getJson($this->url("/document-templates/{$foreign->id}"))->assertNotFound();
        $this->patchJson($this->url("/document-templates/{$foreign->id}"), ['title' => 'Взлом'])->assertNotFound();
        $this->assertSame('Чужой', DocumentTemplate::find($foreign->id)->title);
    }
}
