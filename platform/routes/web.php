<?php

use App\Http\Controllers\MiniApp\AuthController;
use App\Http\Controllers\MiniApp\StartController;
use App\Http\Controllers\Web\TemplateDownloadController;
use App\Http\Middleware\EnsureStudent;
use App\Livewire\MiniApp\AssessmentShow;
use App\Livewire\MiniApp\AttemptResult;
use App\Livewire\MiniApp\AttemptTake;
use App\Livewire\MiniApp\Career;
use App\Livewire\MiniApp\Help;
use App\Livewire\MiniApp\Home;
use App\Livewire\MiniApp\OfferShow;
use App\Livewire\MiniApp\Schedule;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');

Route::get('files/templates/{version}', TemplateDownloadController::class)
    ->middleware('signed')
    ->name('templates.download');

// Мини-приложение MAX
Route::prefix('app')->name('miniapp.')->group(function () {
    Route::get('start', StartController::class)->name('start');
    Route::post('auth', [AuthController::class, 'login'])->middleware('throttle:max-auth')->name('auth');
    Route::post('demo-login', [AuthController::class, 'demo'])->name('demo-login');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', EnsureStudent::class])->group(function () {
        Route::livewire('/', Home::class)->name('home');
        Route::livewire('schedule', Schedule::class)->name('schedule');
        Route::livewire('career', Career::class)->name('career');
        Route::livewire('offers/{offer}', OfferShow::class)->name('offers.show');
        Route::livewire('help', Help::class)->name('help');
        Route::livewire('assessments/{assessment}', AssessmentShow::class)->name('assessments.show');
        Route::livewire('attempts/{attempt}', AttemptTake::class)->name('attempts.take');
        Route::livewire('attempts/{attempt}/result', AttemptResult::class)->name('attempts.result');
    });
});
