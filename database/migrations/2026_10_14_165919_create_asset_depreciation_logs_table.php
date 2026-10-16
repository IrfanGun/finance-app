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
        if (! Schema::hasTable('asset_depreciation_logs')) {
            Schema::create('asset_depreciation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_depreciation_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->date('depreciation_date');
                $table->decimal('value_before', 18, 2);
                $table->decimal('depreciation_amount', 18, 2);
                $table->decimal('value_after', 18, 2);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    ['asset_depreciation_id', 'depreciation_date'],
                    'asset_dep_logs_period_unique',
                );
            });

            return;
        }

        if (! Schema::hasIndex(
            'asset_depreciation_logs',
            ['asset_depreciation_id', 'depreciation_date'],
            'unique',
        )) {
            Schema::table('asset_depreciation_logs', function (Blueprint $table) {
                $table->unique(
                    ['asset_depreciation_id', 'depreciation_date'],
                    'asset_dep_logs_period_unique',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_logs');
    }
};
