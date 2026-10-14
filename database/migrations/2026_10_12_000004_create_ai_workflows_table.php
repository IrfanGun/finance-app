<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('action', 100);
            $table->string('status', 30);
            $table->json('draft');
            $table->json('missing_fields')->nullable();
            $table->json('result')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_workflows');
    }
};
