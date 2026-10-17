<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meal_record_id',
    'day_of_week',
    'opens_at',
    'closes_at',
    'closes_next_day',
])]
class MealOpeningHour extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'closes_next_day' => 'boolean',
        ];
    }

    public function mealRecord(): BelongsTo
    {
        return $this->belongsTo(MealRecord::class);
    }
}
