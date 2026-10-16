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
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('depreciation_type', 20);
            $table->decimal('depreciation_value', 18, 4);
            $table->string('depreciation_period', 20);
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->date('depreciation_start_date');
            $table->date('next_depreciation_date')->nullable();
            $table->date('last_depreciation_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['status', 'next_depreciation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
