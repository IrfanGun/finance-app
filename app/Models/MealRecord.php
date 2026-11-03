<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'place_name',
    'type',
    'prices',
    'price_min',
    'price_max',
    'rating',
    'address',
    'impression',
    'photo_path',
    'photo_paths',
])]
class MealRecord extends Model
{
    protected $hidden = [
        'photo_path',
        'photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'prices' => 'array',
            'photo_paths' => 'array',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'rating' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(MealOpeningHour::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return array<int, string>
     */
    public function photoPaths(): array
    {
        $photoPaths = $this->photo_paths;

        if (is_array($photoPaths) && $photoPaths !== []) {
            return $photoPaths;
        }

        return $this->photo_path !== null
            ? [$this->photo_path]
            : [];
    }
}
