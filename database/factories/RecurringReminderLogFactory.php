<?php

namespace Database\Factories;

use App\Models\RecurringReminder;
use App\Models\RecurringReminderLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringReminderLog>
 */
class RecurringReminderLogFactory extends Factory
{
    protected $model = RecurringReminderLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recurring_reminder_id' => RecurringReminder::factory(),
            'scheduled_at' => CarbonImmutable::now('UTC')->startOfMinute(),
            'status' => 'pending',
            'notified_at' => null,
            'snoozed_until' => null,
            'transaction_id' => null,
        ];
    }
}
