<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_messages', 'request_status')) {
                $table->string('request_status', 20)->nullable()->after('client_request_id');
            }

            if (! Schema::hasColumn('ai_messages', 'response_message_id')) {
                $table->unsignedBigInteger('response_message_id')->nullable()->after('request_status');
            }

            $table->index(
                ['conversation_id', 'client_request_id', 'request_status'],
                'ai_messages_request_lookup_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table): void {
            $table->dropIndex('ai_messages_request_lookup_idx');
            $table->dropColumn(['request_status', 'response_message_id']);
        });
    }
};
