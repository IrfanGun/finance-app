<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'type', 'currency', 'investment_unit', 'opening_balance'])]
class FinancialAccount extends Model
{
    public function currencyCode(): ?string
    {
        if ($this->currency !== null || $this->type === 'investment') {
            return $this->currency;
        }

        return 'IDR';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }
}
