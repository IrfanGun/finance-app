<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'budget_period_id',
    'saving_id',
    'financial_account_id',
    'amount',
    'idempotency_key',
    'claimed_at',
    'reversed_at',
    'reversal_operation_id',
])]
class BudgetSavingsClaim extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'claimed_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(BudgetPeriod::class, 'budget_period_id');
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }
}
