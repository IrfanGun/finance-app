<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_savings_claims', function (Blueprint $table): void {
            $table->dateTime('reversed_at')->nullable();
            $table->uuid('reversal_operation_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('budget_savings_claims')->whereNotNull('reversed_at')->exists()) {
            throw new LogicException('Reversed claims must be reconciled before rollback.');
        }

        Schema::table('budget_savings_claims', function (Blueprint $table): void {
            $table->dropColumn(['reversed_at', 'reversal_operation_id']);
        });
    }
};
