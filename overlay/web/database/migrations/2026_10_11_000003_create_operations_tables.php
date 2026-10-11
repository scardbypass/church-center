<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('church_licenses', function (Blueprint $t) {
            $t->id(); $t->foreignId('church_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('duration_months'); $t->string('status')->default('pending');
            $t->timestamp('starts_at')->nullable(); $t->timestamp('expires_at')->nullable();
            $t->timestamps(); $t->index(['church_id', 'status']);
        });
        Schema::create('support_tickets', function (Blueprint $t) {
            $t->id(); $t->foreignId('church_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('subject', 160); $t->text('message');
            $t->string('status')->default('open'); $t->timestamps();
            $t->index(['church_id', 'status']);
        });
        Schema::create('payment_requests', function (Blueprint $t) {
            $t->id(); $t->foreignId('church_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('duration_months');
            $t->unsignedBigInteger('amount_idr')->nullable();
            $t->string('status')->default('awaiting_review');
            $t->string('provider')->default('manual');
            $t->string('external_reference')->nullable()->unique();
            $t->timestamp('reviewed_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps(); $t->index(['church_id', 'status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('church_licenses');
    }
};
