<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('schedule_source')->nullable();
            $table->timestamp('schedule_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 — понедельник … 7 — воскресенье
            $table->string('parity', 16)->default('every'); // App\Enums\WeekParity
            $table->unsignedTinyInteger('number');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('title');
            $table->string('kind', 16); // App\Enums\LessonKind
            $table->string('teacher')->nullable();
            $table->string('room')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamps();
            $table->index(['study_group_id', 'weekday']);
        });

        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('question');
            $table->text('answer');
            $table->string('owner')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('helpful');
            $table->timestamps();
            $table->unique(['faq_item_id', 'user_id']);
        });

        Schema::create('internship_offers', function (Blueprint $table) {
            $table->id();
            // Вуз, в каталоге которого размещено предложение.
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('company_name');
            $table->string('title');
            $table->string('direction', 16); // App\Enums\OfferDirection
            $table->string('format');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->date('apply_until')->nullable();
            $table->unsignedSmallInteger('places')->default(1);
            $table->json('tasks');
            $table->string('contact')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('internship_offer_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->restrictOnDelete();
            $table->string('level', 16); // App\Enums\SkillLevel
            $table->unique(['internship_offer_id', 'skill_id']);
        });

        Schema::create('internship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16); // App\Enums\ApplicationStatus
            $table->boolean('share_results')->default(false);
            $table->timestamps();
            // Одна запись на студента и предложение: повторная подача не создаёт дубликат.
            $table->unique(['internship_offer_id', 'user_id']);
        });

        Schema::create('application_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_application_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32); // App\Enums\TicketCategory
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('context')->nullable();
            $table->nullableMorphs('subject');
            $table->string('assignee');
            $table->string('status', 16)->default('new'); // App\Enums\TicketStatus
            $table->text('resolution')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['tickets', 'application_status_changes', 'internship_applications', 'internship_offer_skill',
            'internship_offers', 'faq_feedback', 'faq_items', 'lessons', 'students', 'study_groups'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
