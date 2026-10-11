<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('church_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('fulltime');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['church_id', 'user_id']);
            $table->index(['church_id', 'active']);
        });

        Schema::create('church_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['church_id', 'key']);
        });

        Schema::create('church_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_e164', 20);
            $table->date('birthday')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();
            $table->unique(['church_id', 'phone_e164']);
        });

        Schema::create('church_whatsapp_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('disconnected');
            $table->string('session_key')->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique('church_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_whatsapp_connections');
        Schema::dropIfExists('church_contacts');
        Schema::dropIfExists('church_settings');
        Schema::dropIfExists('church_memberships');
        Schema::dropIfExists('churches');
    }
};
