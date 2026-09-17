<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssessmentRequest;
use App\Http\Resources\V1\AssessmentResource;
use App\Models\Assessment;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Тесты
 */
class AssessmentController extends Controller
{
    /** Тесты организации. */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [Assessment::class, $organization]);

        return AssessmentResource::collection(
            $organization->assessments()->with(['publishedVersion' => fn ($q) => $q->withCount('questions')])->orderBy('title')->paginate(50),
        );
    }

    public function store(AssessmentRequest $request, Organization $organization): AssessmentResource
    {
        Gate::authorize('create', [Assessment::class, $organization]);

        return AssessmentResource::make($organization->assessments()->create($request->validated()));
    }

    public function show(Organization $organization, Assessment $assessment): AssessmentResource
    {
        Gate::authorize('view', $assessment);

        return AssessmentResource::make($assessment->load(['publishedVersion' => fn ($q) => $q->withCount('questions')]));
    }

    public function update(AssessmentRequest $request, Organization $organization, Assessment $assessment): AssessmentResource
    {
        Gate::authorize('update', $assessment);

        $assessment->update($request->validated());

        return AssessmentResource::make($assessment);
    }

    public function destroy(Organization $organization, Assessment $assessment): Response
    {
        Gate::authorize('delete', $assessment);

        $assessment->delete();

        return response()->noContent();
    }
}
