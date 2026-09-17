<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('api:token {email} {--name=integration} {--days=90}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("Пользователь {$email} не найден.");

        return 1;
    }

    $token = $user->createToken($this->option('name'), ['*'], now()->addDays((int) $this->option('days')));
    $this->info('Токен (показывается один раз):');
    $this->line($token->plainTextToken);

    return 0;
})->purpose('Выдать API-токен пользователю организации для интеграции');
