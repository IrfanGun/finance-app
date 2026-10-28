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
            $table->dropForeign(['budget_period_id']);
            $table->unsignedBigInteger('budget_period_id')->nullable()->change();
            $table->foreign('budget_period_id')
                ->references('id')
                ->on('budget_periods')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('budget_savings_claims')->whereNull('budget_period_id')->exists()) {
            throw new LogicException('Detached budget claims must be reconciled before rollback.');
        }

        Schema::table('budget_savings_claims', function (Blueprint $table): void {
            $table->dropForeign(['budget_period_id']);
            $table->unsignedBigInteger('budget_period_id')->nullable(false)->change();
            $table->foreign('budget_period_id')
                ->references('id')
                ->on('budget_periods')
                ->restrictOnDelete();
        });
    }
};
