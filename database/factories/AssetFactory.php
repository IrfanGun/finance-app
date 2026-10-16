<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'currency' => 'IDR',
            'purchase_price' => '12000000.00',
            'purchase_date' => now()->subYear()->toDateString(),
            'current_value' => '12000000.00',
            'status' => Asset::STATUS_ACTIVE,
        ];
    }
}
