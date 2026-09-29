<?php

namespace App\Providers;

use App\Models\User;
use App\Notifications\Channels\MaxChannel;
use App\Support\Max\InitDataValidator;
use App\Support\Max\MaxApi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Не singleton: токен и адрес читаются из конфигурации при каждом обращении.
        $this->app->bind(MaxApi::class, fn () => new MaxApi(
            (string) config('services.max.bot_token'),
            (string) config('services.max.api_url'),
        ));

        $this->app->bind(InitDataValidator::class, fn () => new InitDataValidator(
            (string) config('services.max.bot_token'),
            (int) config('services.max.init_data_ttl'),
        ));
    }

    public function boot(): void
    {
        // Канал уведомлений MAX: маршрут берётся из User::routeNotificationForMax().
        Notification::extend('max', fn ($app) => $app->make(MaxChannel::class));

        // Документация API открыта: по ней подключаются вузы и проверяющие хакатона.
        Gate::define('viewApiDocs', fn (?User $user = null) => true);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        // Ограничение API MAX — 30 запросов в секунду на бота.
        RateLimiter::for('max-api', fn () => Limit::perSecond(20));

        RateLimiter::for('max-auth', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
    }
}
