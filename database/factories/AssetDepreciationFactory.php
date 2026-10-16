<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDepreciation>
 */
class AssetDepreciationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'depreciation_type' => AssetDepreciation::TYPE_PERCENTAGE,
            'is_tiered' => false,
            'depreciation_value' => '5.0000',
            'depreciation_period' => 'yearly',
            'residual_value' => '0.00',
            'depreciation_start_date' => now()->subYear()->toDateString(),
            'next_depreciation_date' => now()->toDateString(),
            'last_depreciation_date' => null,
            'status' => AssetDepreciation::STATUS_ACTIVE,
        ];
    }
}
