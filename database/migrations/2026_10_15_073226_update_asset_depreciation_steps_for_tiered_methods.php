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
        Schema::table('asset_depreciations', function (Blueprint $table): void {
            $table->boolean('is_tiered')
                ->default(false)
                ->after('depreciation_type');
        });

        Schema::table('asset_depreciation_steps', function (Blueprint $table): void {
            $table->decimal('depreciation_value', 18, 4)
                ->default('0.0000')
                ->after('year_number');
        });

        DB::table('asset_depreciation_steps')->update([
            'depreciation_value' => DB::raw('rate_percent'),
        ]);

        DB::table('asset_depreciations')
            ->where('depreciation_type', 'tiered_percentage')
            ->update([
                'depreciation_type' => 'percentage',
                'is_tiered' => true,
            ]);

        Schema::table('asset_depreciation_steps', function (Blueprint $table): void {
            $table->dropColumn('rate_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasFixedTieredSettings = DB::table('asset_depreciations')
            ->where('is_tiered', true)
            ->where('depreciation_type', 'fixed')
            ->exists();

        if ($hasFixedTieredSettings) {
            throw new RuntimeException(
                'Disable fixed tiered depreciation before rolling back this migration.',
            );
        }

        Schema::table('asset_depreciation_steps', function (Blueprint $table): void {
            $table->decimal('rate_percent', 7, 4)
                ->default('0.0000')
                ->after('year_number');
        });

        DB::table('asset_depreciation_steps')->update([
            'rate_percent' => DB::raw('depreciation_value'),
        ]);

        Schema::table('asset_depreciation_steps', function (Blueprint $table): void {
            $table->dropColumn('depreciation_value');
        });

        DB::table('asset_depreciations')
            ->where('is_tiered', true)
            ->where('depreciation_type', 'percentage')
            ->update([
                'depreciation_type' => 'tiered_percentage',
            ]);

        Schema::table('asset_depreciations', function (Blueprint $table): void {
            $table->dropColumn('is_tiered');
        });
    }
};
