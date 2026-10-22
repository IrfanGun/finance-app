<?php

namespace App\Notifications;

use App\Models\BudgetPeriod;
use Illuminate\Notifications\Notification;

class BudgetFundingNeeded extends Notification
{
    public function __construct(public BudgetPeriod $period) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Budget '.$this->period->plan->name.' menunggu dana',
            'budget_plan_id' => $this->period->budget_plan_id,
            'budget_period_id' => $this->period->id,
            'url' => route('budget-plans.index', [
                'plan' => $this->period->budget_plan_id,
                'period' => $this->period->id,
            ]),
        ];
    }
}
