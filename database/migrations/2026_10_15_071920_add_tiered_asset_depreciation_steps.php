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
        Schema::create('asset_depreciation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_depreciation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('year_number');
            $table->decimal('rate_percent', 7, 4);
            $table->timestamps();

            $table->unique(
                ['asset_depreciation_id', 'year_number'],
                'asset_dep_steps_year_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_steps');
    }
};
