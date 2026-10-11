<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('church_recurring_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('church_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 32);
            $t->string('target', 120);
            $t->text('message');
            $t->string('local_time', 5);
            $t->string('timezone', 64)->default('Asia/Jakarta');
            $t->boolean('enabled')->default(false);
            $t->timestamps();
            $t->index(['enabled', 'church_id']);
        });
        Schema::create('church_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('church_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 100);
            $t->json('details')->nullable();
            $t->timestamps();
            $t->index(['church_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('church_audit_logs');
        Schema::dropIfExists('church_recurring_messages');
    }
};
