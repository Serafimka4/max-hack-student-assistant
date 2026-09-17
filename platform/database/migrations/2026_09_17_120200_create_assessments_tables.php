<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('direction');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('retake_after_days')->default(14);
            $table->timestamps();
        });

        Schema::create('assessment_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('notes')->nullable();
            // Опубликованная версия неизменяема.
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'version']);
        });

        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->string('type', 16); // App\Enums\QuestionType
            $table->text('prompt');
            $table->text('code')->nullable();
            $table->json('options'); // [{key, text}]
            $table->json('correct_keys'); // никогда не отдаётся клиенту
            $table->unsignedSmallInteger('points')->default(1);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessment_versions');
        Schema::dropIfExists('assessments');
    }
};
