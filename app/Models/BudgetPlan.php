<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'type',
    'amount',
    'target_amount',
    'target_date',
    'saving_id',
    'contribution_amount',
    'currency',
    'category_id',
    'financial_account_id',
    'is_recurring',
    'frequency',
    'interval',
    'starts_on',
    'ends_on',
    'timezone',
    'next_period_starts_on',
    'status',
    'notes',
])]
class BudgetPlan extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'target_amount' => 'decimal:2',
            'contribution_amount' => 'decimal:2',
            'target_date' => 'date:Y-m-d',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'next_period_starts_on' => 'date:Y-m-d',
            'is_recurring' => 'boolean',
            'interval' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetPlanItem::class);
    }
}
