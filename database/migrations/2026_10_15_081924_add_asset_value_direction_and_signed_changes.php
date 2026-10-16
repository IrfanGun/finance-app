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
            $table->string('value_direction', 10)
                ->default('decrease')
                ->after('is_tiered');
        });

        Schema::table('asset_depreciation_logs', function (Blueprint $table): void {
            $table->decimal('value_change', 18, 2)
                ->default('0.00')
                ->after('asset_depreciation_id');
        });

        DB::table('asset_depreciation_logs')->update([
            'value_change' => DB::raw('-depreciation_amount'),
        ]);

        Schema::table('asset_depreciation_logs', function (Blueprint $table): void {
            $table->dropColumn('depreciation_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('asset_depreciation_logs')->where('value_change', '>', 0)->exists()) {
            throw new RuntimeException(
                'Remove appreciation log entries before rolling back this migration.',
            );
        }

        Schema::table('asset_depreciation_logs', function (Blueprint $table): void {
            $table->decimal('depreciation_amount', 18, 2)
                ->default('0.00')
                ->after('asset_depreciation_id');
        });

        DB::table('asset_depreciation_logs')->update([
            'depreciation_amount' => DB::raw('-value_change'),
        ]);

        Schema::table('asset_depreciation_logs', function (Blueprint $table): void {
            $table->dropColumn('value_change');
        });

        Schema::table('asset_depreciations', function (Blueprint $table): void {
            $table->dropColumn('value_direction');
        });
    }
};
