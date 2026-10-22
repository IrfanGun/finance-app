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
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('status', 20)->default('posted')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('transactions')->where('status', 'void')->exists()) {
            throw new LogicException('Voided transactions must be reconciled before rollback.');
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
