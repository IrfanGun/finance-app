<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->foreignId('account_id')->nullable()->change();
            $table->foreign('account_id')->references('id')->on('financial_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
            $table->foreignId('account_id')->nullable(false)->change();
            $table->foreign('account_id')->references('id')->on('financial_accounts')->restrictOnDelete();
        });
    }
};
