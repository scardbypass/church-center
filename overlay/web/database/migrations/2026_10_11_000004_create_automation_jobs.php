<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('church_automation_jobs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('church_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 32);
            $t->string('target', 120);
            $t->text('message');
            $t->timestamp('scheduled_at');
            $t->string('status', 24)->default('draft');
            $t->string('idempotency_key', 100)->unique();
            $t->unsignedInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'scheduled_at']);
            $t->index(['church_id', 'kind']);
        });
    }
    public function down(): void { Schema::dropIfExists('church_automation_jobs'); }
};
