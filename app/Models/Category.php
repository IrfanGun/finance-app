<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'type', 'icon', 'color', 'is_active'])]
class Category extends Model
{
    public const DEFAULT_COLOR = '#2457DA';

    public const DEFAULT_ICON = 'heart';

    public const COLORS = [
        '#2457DA',
        '#16815B',
        '#B34735',
        '#A8750D',
        '#7A52B9',
        '#637083',
    ];

    public const ICONS = [
        'heart',
        'home',
        'arrow',
        'food',
        'shopping',
        'transport',
        'coffee',
        'work',
        'education',
        'travel',
        'gift',
        'phone',
        'internet',
        'electricity',
        'health',
        'fitness',
        'music',
        'games',
        'pets',
        'clothing',
        'square',
        'more',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
