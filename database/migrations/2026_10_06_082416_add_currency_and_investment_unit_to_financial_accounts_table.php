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
        Schema::table('financial_accounts', function (Blueprint $table): void {
            $table->string('currency', 3)
                ->nullable()
                ->after('type');
            $table->string('investment_unit', 10)
                ->nullable()
                ->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'currency',
                'investment_unit',
            ]);
        });
    }
};
