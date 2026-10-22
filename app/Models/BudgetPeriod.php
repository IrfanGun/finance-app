<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'budget_plan_id',
    'starts_on',
    'ends_on',
    'financial_account_id',
    'currency',
    'planned_amount',
    'planned_amount_origin',
    'allocated_amount',
    'released_amount',
    'status',
    'funded_at',
    'closed_at',
    'contribution_completed_at',
])]
class BudgetPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'planned_amount' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'released_amount' => 'decimal:2',
            'funded_at' => 'datetime',
            'closed_at' => 'datetime',
            'contribution_completed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(BudgetSavingsClaim::class);
    }
}
