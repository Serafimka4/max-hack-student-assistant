<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_versions', function (Blueprint $table) {
            // Правила оценки версии: порог базового уровня и минимум вопросов на навык.
            $table->unsignedTinyInteger('basic_threshold')->default(60);
            $table->unsignedTinyInteger('min_questions_per_skill')->default(2);
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('expired')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'submitted_at']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_question_id')->constrained()->restrictOnDelete();
            $table->json('selected_keys');
            $table->unsignedSmallInteger('points_awarded')->nullable();
            $table->timestamps();
            $table->unique(['assessment_attempt_id', 'assessment_question_id']);
        });

        Schema::create('skill_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('points');
            $table->unsignedSmallInteger('max_points');
            $table->unsignedSmallInteger('questions_count');
            $table->string('level', 16)->nullable(); // App\Enums\SkillLevel
            $table->string('outcome', 24); // App\Enums\SkillOutcome
            $table->timestamps();
            $table->unique(['assessment_attempt_id', 'skill_id']);
            $table->index(['user_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_results');
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::table('assessment_versions', fn (Blueprint $table) => $table->dropColumn(['basic_threshold', 'min_questions_per_skill']));
    }
};
