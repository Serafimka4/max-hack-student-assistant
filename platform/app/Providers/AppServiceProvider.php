<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Max\InitDataValidator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InitDataValidator::class, fn () => new InitDataValidator(
            (string) config('services.max.bot_token'),
            (int) config('services.max.init_data_ttl'),
        ));
    }

    public function boot(): void
    {
        // Документация API открыта: по ней подключаются вузы и проверяющие хакатона.
        Gate::define('viewApiDocs', fn (?User $user = null) => true);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('max-auth', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
