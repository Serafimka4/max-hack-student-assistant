<?php

use App\Http\Controllers\Web\TemplateDownloadController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('files/templates/{version}', TemplateDownloadController::class)
    ->middleware('signed')
    ->name('templates.download');
