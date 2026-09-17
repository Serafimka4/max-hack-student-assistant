<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Мини-приложение доступно только студентам вуза-участника. */
class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->student) {
            return redirect()->route('miniapp.start', ['reason' => 'no-access']);
        }

        return $next($request);
    }
}
