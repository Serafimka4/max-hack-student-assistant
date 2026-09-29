<?php

namespace App\Http\Controllers\MiniApp;

use App\Actions\Auth\AuthenticateMaxUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MaxAuthRequest;
use App\Models\AssessmentAttempt;
use App\Models\InternshipOffer;
use App\Models\User;
use App\Support\Max\InvalidInitDataException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /** Вход в мини-приложение по initData MAX: создаёт сессию и возвращает адрес экрана. */
    public function login(MaxAuthRequest $request, AuthenticateMaxUser $authenticate): JsonResponse
    {
        try {
            ['user' => $user, 'start_param' => $startParam] = $authenticate($request->validated('init_data'));
        } catch (InvalidInitDataException) {
            return response()->json(['message' => 'Не удалось подтвердить вход через MAX. Откройте приложение заново.'], 401);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['redirect' => $this->redirectFor($startParam)]);
    }

    /** Вход демо-студентом для локальной проверки без MAX. */
    public function demo(): JsonResponse
    {
        abort_unless(config('miniapp.demo_login'), 404);

        Auth::loginUsingId(User::where('email', 'student@demo.test')->valueOrFail('id'));
        request()->session()->regenerate();

        return response()->json(['redirect' => route('miniapp.home')]);
    }

    public function logout(): JsonResponse
    {
        Auth::logout();
        request()->session()->invalidate();

        return response()->json(['redirect' => route('miniapp.start')]);
    }

    /**
     * Диплинк https://max.ru/<bot>?startapp=<параметр> открывает нужный экран:
     * schedule, career, help, offer_<id>, attempt_<id>.
     */
    private function redirectFor(?string $startParam): string
    {
        $startParam = (string) $startParam;

        if (preg_match('/^offer_(\d+)$/', $startParam, $m) && InternshipOffer::whereKey($m[1])->exists()) {
            return route('miniapp.offers.show', $m[1]);
        }

        if (preg_match('/^attempt_(\d+)$/', $startParam, $m)
            && AssessmentAttempt::whereKey($m[1])->whereBelongsTo(Auth::user())->whereNotNull('submitted_at')->exists()) {
            return route('miniapp.attempts.result', $m[1]);
        }

        return match ($startParam) {
            'schedule' => route('miniapp.schedule'),
            'career' => route('miniapp.career'),
            'help' => route('miniapp.help'),
            default => route('miniapp.home'),
        };
    }
}
