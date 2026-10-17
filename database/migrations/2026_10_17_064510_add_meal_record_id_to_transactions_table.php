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
        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('meal_record_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['user_id', 'meal_record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'meal_record_id']);
            $table->dropConstrainedForeignId('meal_record_id');
        });
    }
};
