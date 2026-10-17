<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
])]

class Transaction extends Model
{
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
}
