<?php

namespace App\Console\Commands;

use App\Services\RecurringReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:process-recurring-reminders')]
#[Description('Create due recurring reminder logs and in-app notifications.')]
class ProcessRecurringReminders extends Command
{
    public function handle(RecurringReminderService $reminders): int
    {
        $result = $reminders->processDue();

        $this->info(sprintf(
            'Created %d reminder logs and sent %d notifications.',
            $result['logs_created'],
            $result['notifications_sent'],
        ));

        return self::SUCCESS;
    }
}
