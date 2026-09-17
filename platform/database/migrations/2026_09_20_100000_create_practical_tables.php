<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_versions', function (Blueprint $table) {
            $table->text('practical_task')->nullable();
            $table->json('practical_rubric')->nullable(); // [{skill_id, criterion, max_points}]
            $table->unsignedTinyInteger('applied_threshold')->default(70);
            $table->unsignedTinyInteger('confident_threshold')->default(90);
        });

        Schema::table('skill_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('practical_points')->nullable();
            $table->unsignedSmallInteger('practical_max_points')->nullable();
        });

        Schema::create('practical_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_attempt_id')->unique()->constrained()->cascadeOnDelete();
            // Организация — автор теста: её проверяющие оценивают решение.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('link', 500)->nullable();
            $table->text('answer')->nullable();
            $table->string('status', 16); // App\Enums\PracticalStatus
            $table->text('appeal_reason')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('practical_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practical_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('scores'); // баллы по критериям рубрики, в порядке рубрики
            $table->text('comment')->nullable();
            $table->boolean('is_appeal')->default(false);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practical_reviews');
        Schema::dropIfExists('practical_submissions');
        Schema::table('skill_results', fn (Blueprint $table) => $table->dropColumn(['practical_points', 'practical_max_points']));
        Schema::table('assessment_versions', fn (Blueprint $table) => $table->dropColumn(['practical_task', 'practical_rubric', 'applied_threshold', 'confident_threshold']));
    }
};
