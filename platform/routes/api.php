<?php

use App\Http\Controllers\Api\V1\AssessmentController;
use App\Http\Controllers\Api\V1\AssessmentVersionController;
use App\Http\Controllers\Api\V1\DocumentTemplateController;
use App\Http\Controllers\Api\V1\MaxAuthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/max', MaxAuthController::class)->middleware('throttle:max-auth')->name('auth.max');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('me', MeController::class)->name('me');

        Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::get('organizations/{organization:slug}', [OrganizationController::class, 'show'])->name('organizations.show');
        Route::get('organizations/{organization:slug}/skills', [OrganizationController::class, 'skills'])->name('organizations.skills');

        Route::scopeBindings()->prefix('organizations/{organization:slug}')->group(function () {
            Route::apiResource('document-templates', DocumentTemplateController::class);
            Route::get('document-templates/{document_template}/versions', [DocumentTemplateController::class, 'versions'])->name('document-templates.versions.index');
            Route::post('document-templates/{document_template}/versions', [DocumentTemplateController::class, 'storeVersion'])->name('document-templates.versions.store');

            Route::apiResource('assessments', AssessmentController::class);
            Route::get('assessments/{assessment}/versions', [AssessmentVersionController::class, 'index'])->name('assessments.versions.index');
            Route::post('assessments/{assessment}/versions', [AssessmentVersionController::class, 'store'])->name('assessments.versions.store');
            Route::get('assessments/{assessment}/versions/{version}', [AssessmentVersionController::class, 'show'])->name('assessments.versions.show');
            Route::put('assessments/{assessment}/versions/{version}', [AssessmentVersionController::class, 'update'])->name('assessments.versions.update');
            Route::post('assessments/{assessment}/versions/{version}/publish', [AssessmentVersionController::class, 'publish'])->name('assessments.versions.publish');
        });
    });
});
