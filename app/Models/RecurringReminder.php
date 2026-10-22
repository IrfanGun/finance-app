<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'type',
    'priority',
    'title',
    'amount',
    'financial_account_id',
    'category_id',
    'frequency',
    'interval',
    'starts_at',
    'next_reminder_at',
    'ends_at',
    'timezone',
    'status',
    'notes',
    'purpose',
    'budget_plan_id',
    'saving_id',
])]
class RecurringReminder extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'interval' => 'integer',
            'starts_at' => 'immutable_datetime',
            'next_reminder_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RecurringReminderLog::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }
}
