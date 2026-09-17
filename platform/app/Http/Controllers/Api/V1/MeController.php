<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrganizationResource;
use Illuminate\Http\Request;

/**
 * @tags Профиль
 */
class MeController extends Controller
{
    /** Текущий пользователь и его организации. */
    public function __invoke(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'is_platform_admin' => $user->is_platform_admin,
            'organizations' => OrganizationResource::collection($user->organizations),
        ];
    }
}
