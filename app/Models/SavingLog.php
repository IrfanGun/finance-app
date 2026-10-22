<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'saving_id',
    'financial_account_id',
    'type',
    'amount',
    'reason',
    'budget_savings_claim_id',
    'budget_period_id',
    'transaction_id',
    'operation_id',
    'idempotency_key',
    'reverses_log_id',
    'occurred_at',
    'notes',
])]
class SavingLog extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Saving logs are append only.');
        });
        static::deleting(function (): never {
            throw new \LogicException('Saving logs are append only.');
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(SavingLog::class, 'reverses_log_id');
    }

    public function reversedOriginal(): BelongsTo
    {
        return $this->belongsTo(SavingLog::class, 'reverses_log_id');
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(BudgetSavingsClaim::class, 'budget_savings_claim_id');
    }
}
