<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'name', 'currency', 'icon', 'color', 'target_amount', 'target_date', 'status', 'notes'])]
class Saving extends Model
{
    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'target_date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SavingLog::class);
    }

    public function goalPlan(): HasOne
    {
        return $this->hasOne(BudgetPlan::class);
    }
}
