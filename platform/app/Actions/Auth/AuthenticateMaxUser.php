<?php

namespace App\Actions\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Support\Max\InitDataValidator;
use App\Support\Max\InvalidInitDataException;
use Illuminate\Support\Facades\DB;

/**
 * Проверяет initData MAX и возвращает пользователя платформы. Общая логика для API и мини-приложения.
 *
 * @throws InvalidInitDataException
 */
class AuthenticateMaxUser
{
    public function __construct(private InitDataValidator $validator) {}

    /** @return array{user: User, start_param: ?string} */
    public function __invoke(string $initData): array
    {
        $data = $this->validator->validate($initData);
        $maxUser = $data['user'];

        $user = DB::transaction(function () use ($maxUser) {
            $user = User::firstOrNew(['max_user_id' => $maxUser['id']]);
            $user->name = trim(($maxUser['first_name'] ?? '').' '.($maxUser['last_name'] ?? '')) ?: 'Пользователь MAX';
            $user->save();

            $this->enrollForPilot($user);

            return $user;
        });

        return ['user' => $user, 'start_param' => $data['start_param']];
    }

    private function enrollForPilot(User $user): void
    {
        $slug = config('miniapp.enroll_organization');

        if (! $slug || $user->student()->exists()) {
            return;
        }

        $organization = Organization::where('slug', $slug)->first();
        $organization && $user->student()->create(['organization_id' => $organization->id]);
    }
}
