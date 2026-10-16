<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'asset_depreciation_id',
    'depreciation_date',
    'value_before',
    'value_change',
    'value_after',
])]
class AssetDepreciationLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'depreciation_date' => 'date',
            'value_before' => 'decimal:2',
            'value_change' => 'decimal:2',
            'value_after' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function depreciation(): BelongsTo
    {
        return $this->belongsTo(AssetDepreciation::class, 'asset_depreciation_id');
    }
}
