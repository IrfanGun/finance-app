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
        Schema::table('meal_records', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->nullable()->after('price_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_records', function (Blueprint $table): void {
            $table->dropColumn('rating');
        });
    }
};
