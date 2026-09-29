<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('handled_by')->nullable()->after('assignee')->constrained('users')->nullOnDelete();
        });

        // Отметка работодателя «заинтересован»: следующий шаг делает координатор.
        Schema::create('employer_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['internship_application_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_interests');
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
        });
    }
};
