<?php

namespace App\Models;

use Database\Factories\AssetDepreciationStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_depreciation_id', 'year_number', 'depreciation_value'])]
class AssetDepreciationStep extends Model
{
    /** @use HasFactory<AssetDepreciationStepFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year_number' => 'integer',
            'depreciation_value' => 'decimal:4',
        ];
    }

    public function depreciation(): BelongsTo
    {
        return $this->belongsTo(AssetDepreciation::class, 'asset_depreciation_id');
    }
}
