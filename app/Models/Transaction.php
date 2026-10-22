<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'account_id',
    'category_id',
    'meal_record_id',
    'type',
    'amount',
    'date',
    'title',
    'note',
    'budget_period_id',
    'budget_plan_id',
    'saving_id',
    'status',
])]

class Transaction extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(
            'posted',
            fn (Builder $query) => $query->where('transactions.status', 'posted'),
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    public function mealRecord(): BelongsTo
    {
        return $this->belongsTo(MealRecord::class);
    }

    public function recurringReminderLog(): HasOne
    {
        return $this->hasOne(RecurringReminderLog::class);
    }

    public function budgetPeriod(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class);
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class);
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }
}
