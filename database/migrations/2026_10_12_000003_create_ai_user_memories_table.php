<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_memories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('memory_key', 100);
            $table->json('value');
            $table->foreignId('source_message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'memory_key']);
            $table->index('source_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_memories');
    }
};
