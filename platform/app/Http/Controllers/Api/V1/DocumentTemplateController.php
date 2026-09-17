<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Templates\StoreTemplateVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DocumentTemplateRequest;
use App\Http\Requests\Api\V1\TemplateVersionRequest;
use App\Http\Resources\V1\DocumentTemplateResource;
use App\Http\Resources\V1\DocumentTemplateVersionResource;
use App\Models\DocumentTemplate;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Шаблоны документов
 */
class DocumentTemplateController extends Controller
{
    /**
     * Шаблоны организации.
     *
     * Участники организации видят все шаблоны, остальные — только опубликованные.
     */
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $query = $organization->documentTemplates()->with('currentVersion')->orderBy('title');

        if (! $request->user()->can('viewAny', [DocumentTemplate::class, $organization])) {
            $query->where('is_published', true);
        }

        return DocumentTemplateResource::collection($query->paginate(50));
    }

    public function store(DocumentTemplateRequest $request, Organization $organization): DocumentTemplateResource
    {
        Gate::authorize('create', [DocumentTemplate::class, $organization]);

        $template = $organization->documentTemplates()->create($request->validated());

        return DocumentTemplateResource::make($template->load('currentVersion'));
    }

    public function show(Organization $organization, DocumentTemplate $documentTemplate): DocumentTemplateResource
    {
        Gate::authorize('view', $documentTemplate);

        return DocumentTemplateResource::make($documentTemplate->load('currentVersion'));
    }

    public function update(DocumentTemplateRequest $request, Organization $organization, DocumentTemplate $documentTemplate): DocumentTemplateResource
    {
        Gate::authorize('update', $documentTemplate);

        $documentTemplate->update($request->validated());

        return DocumentTemplateResource::make($documentTemplate->load('currentVersion'));
    }

    public function destroy(Organization $organization, DocumentTemplate $documentTemplate): Response
    {
        Gate::authorize('delete', $documentTemplate);

        $documentTemplate->delete();

        return response()->noContent();
    }

    /** История версий файла. */
    public function versions(Organization $organization, DocumentTemplate $documentTemplate): AnonymousResourceCollection
    {
        Gate::authorize('update', $documentTemplate);

        return DocumentTemplateVersionResource::collection($documentTemplate->versions);
    }

    /**
     * Загрузить новую версию файла.
     *
     * Предыдущие версии сохраняются; актуальной становится загруженная.
     */
    public function storeVersion(TemplateVersionRequest $request, Organization $organization, DocumentTemplate $documentTemplate, StoreTemplateVersion $store): DocumentTemplateVersionResource
    {
        Gate::authorize('update', $documentTemplate);

        return DocumentTemplateVersionResource::make($store($documentTemplate, $request->file('file'), $request->user()));
    }
}
