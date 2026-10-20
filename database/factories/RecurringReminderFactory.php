<?php

namespace Database\Factories;

use App\Models\RecurringReminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringReminder>
 */
class RecurringReminderFactory extends Factory
{
    protected $model = RecurringReminder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = CarbonImmutable::now('UTC')->addDay()->startOfMinute();

        return [
            'user_id' => User::factory(),
            'type' => 'expense',
            'priority' => 'medium',
            'title' => fake()->sentence(3),
            'amount' => fake()->numberBetween(1, 1000000).'.00',
            'financial_account_id' => null,
            'category_id' => null,
            'frequency' => 'monthly',
            'interval' => 1,
            'starts_at' => $startsAt,
            'next_reminder_at' => $startsAt,
            'ends_at' => null,
            'timezone' => 'Asia/Jakarta',
            'status' => 'active',
            'notes' => null,
        ];
    }
}
