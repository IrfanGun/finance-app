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
        $url = match ($this->reminder->purpose) {
            'budget_allocation', 'budget_savings_claim', 'goal_contribution' => route(
                'budget-plans.index',
                ['plan' => $this->reminder->budget_plan_id, 'period' => $this->log->budget_period_id],
            ),
            'saving_allocation' => route(
                'savings.index',
                [
                    'saving' => $this->reminder->saving_id,
                    'reminder_log' => $this->log->id,
                ],
            ),
            default => route('recurring-reminders.transaction', [
                'reminder' => $this->reminder->id,
                'log' => $this->log->id,
            ]),
        };

        return [
            'title' => $this->reminder->title,
            'type' => $this->reminder->type,
            'purpose' => $this->reminder->purpose,
            'budget_period_id' => $this->log->budget_period_id,
            'amount' => $this->reminder->amount,
            'currency' => $this->reminder->account?->currency,
            'scheduled_at' => $this->log->scheduled_at->toIso8601String(),
            'reminder_log_id' => $this->log->id,
            'kind' => $this->kind,
            'url' => $url,
        ];
    }
}
