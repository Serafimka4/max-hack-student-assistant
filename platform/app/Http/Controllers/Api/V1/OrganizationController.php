<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrganizationResource;
use App\Http\Resources\V1\SkillResource;
use App\Models\Organization;
use App\Models\Skill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Организации
 */
class OrganizationController extends Controller
{
    /** Организации, подключённые к платформе. */
    public function index(): AnonymousResourceCollection
    {
        return OrganizationResource::collection(Organization::orderBy('name')->get());
    }

    public function show(Organization $organization): OrganizationResource
    {
        return OrganizationResource::make($organization);
    }

    /** Навыки, доступные организации: общий справочник и собственные. */
    public function skills(Organization $organization): AnonymousResourceCollection
    {
        return SkillResource::collection(
            Skill::availableTo($organization)->orderBy('direction')->orderBy('title')->get(),
        );
    }
}
