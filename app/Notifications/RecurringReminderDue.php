<?php

namespace App\Notifications;

use App\Models\RecurringReminder;
use App\Models\RecurringReminderLog;
use Illuminate\Notifications\Notification;

class RecurringReminderDue extends Notification
{
    public function __construct(
        public RecurringReminder $reminder,
        public RecurringReminderLog $log,
        public string $kind = 'initial',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->reminder->title,
            'type' => $this->reminder->type,
            'amount' => $this->reminder->amount,
            'currency' => $this->reminder->account?->currency,
            'scheduled_at' => $this->log->scheduled_at->toIso8601String(),
            'reminder_log_id' => $this->log->id,
            'kind' => $this->kind,
            'url' => route('recurring-reminders.transaction', [
                'reminder' => $this->reminder->id,
                'log' => $this->log->id,
            ]),
        ];
    }
}
