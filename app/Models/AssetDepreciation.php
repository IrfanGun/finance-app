<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'asset_id',
    'depreciation_type',
    'is_tiered',
    'value_direction',
    'depreciation_value',
    'depreciation_period',
    'residual_value',
    'depreciation_start_date',
    'next_depreciation_date',
    'last_depreciation_date',
    'status',
])]
class AssetDepreciation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENTAGE = 'percentage';

    public const DIRECTION_DECREASE = 'decrease';

    public const DIRECTION_INCREASE = 'increase';

    protected $attributes = [
        'is_tiered' => false,
        'value_direction' => self::DIRECTION_DECREASE,
        'residual_value' => '0.00',
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'depreciation_value' => 'decimal:4',
            'is_tiered' => 'boolean',
            'residual_value' => 'decimal:2',
            'depreciation_start_date' => 'date',
            'next_depreciation_date' => 'date',
            'last_depreciation_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AssetDepreciationLog::class);
    }

    public function rateSteps(): HasMany
    {
        return $this->hasMany(AssetDepreciationStep::class)
            ->orderBy('year_number');
    }
}
