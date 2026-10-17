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
        if (! Schema::hasColumn('financial_accounts', 'currency')) {
            Schema::table('financial_accounts', function (Blueprint $table): void {
                $table->string('currency', 3)->nullable();
            });
        }

        if (! Schema::hasColumn('financial_accounts', 'investment_unit')) {
            Schema::table('financial_accounts', function (Blueprint $table): void {
                $table->string('investment_unit', 10)->nullable();
            });
        }
    }

    /**
     * Preserve columns that may have existed before this migration ran.
     */
    public function down(): void {}
};
