<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // App\Enums\OrganizationType
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32); // App\Enums\MemberRole
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            // null — общий справочник платформы, иначе навык конкретной организации.
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->string('title');
            $table->timestamps();
            $table->unique(['organization_id', 'direction', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
