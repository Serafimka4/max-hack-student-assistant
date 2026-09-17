<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateMaxUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MaxAuthRequest;
use App\Support\Max\InvalidInitDataException;
use Illuminate\Http\JsonResponse;

/**
 * @tags Авторизация
 */
class MaxAuthController extends Controller
{
    /**
     * Вход по данным запуска мини-приложения MAX.
     *
     * Проверяет подпись initData токеном бота и выдаёт токен API.
     *
     * @unauthenticated
     */
    public function __invoke(MaxAuthRequest $request, AuthenticateMaxUser $authenticate): JsonResponse
    {
        try {
            ['user' => $user, 'start_param' => $startParam] = $authenticate($request->validated('init_data'));
        } catch (InvalidInitDataException $e) {
            return response()->json(['message' => 'Данные запуска MAX не прошли проверку: '.$e->getMessage()], 401);
        }

        return response()->json([
            /** Передавайте в заголовке Authorization: Bearer <token>. */
            'token' => $user->createToken('max-miniapp', ['*'], now()->addDay())->plainTextToken,
            'user' => ['id' => $user->id, 'name' => $user->name],
            'start_param' => $startParam,
        ]);
    }
}
