<?php

namespace Database\Factories;

use App\Models\AssetDepreciation;
use App\Models\AssetDepreciationStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDepreciationStep>
 */
class AssetDepreciationStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_depreciation_id' => AssetDepreciation::factory(),
            'year_number' => 1,
            'depreciation_value' => '15.0000',
        ];
    }
}
