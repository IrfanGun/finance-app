<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'name',
    'type',
    'icon',
    'color',
    'amount',
    'target_amount',
    'target_date',
    'saving_id',
    'contribution_amount',
    'currency',
    'category_id',
    'financial_account_id',
    'is_recurring',
    'frequency',
    'interval',
    'starts_on',
    'ends_on',
    'timezone',
    'next_period_starts_on',
    'status',
    'notes',
])]
class BudgetPlan extends Model
{
    public const ICONS = [
        'shopping-bag' => 'Belanja',
        'target' => 'Target',
        'wallet' => 'Dompet',
        'house' => 'Rumah',
        'car-front' => 'Kendaraan',
        'plane' => 'Perjalanan',
        'footprints' => 'Sepatu',
        'heart-pulse' => 'Kesehatan',
        'graduation-cap' => 'Pendidikan',
        'gift' => 'Hadiah',
        'coffee' => 'Kopi',
        'briefcase-business' => 'Pekerjaan',
        'music' => 'Hobi',
        'paw-print' => 'Hewan peliharaan',
        'shopping-cart' => 'Keranjang belanja',
        'bus' => 'Bus',
        'bike' => 'Sepeda',
        'building-2' => 'Gedung',
        'camera' => 'Kamera',
        'circle-dollar-sign' => 'Keuangan',
        'clapperboard' => 'Film',
        'dumbbell' => 'Olahraga',
        'gamepad-2' => 'Permainan',
        'laptop' => 'Laptop',
        'lightbulb' => 'Ide',
        'piggy-bank' => 'Tabungan',
        'shirt' => 'Pakaian',
        'smartphone' => 'Ponsel',
    ];

    public const COLORS = [
        'blue' => 'Biru',
        'red' => 'Merah',
        'green' => 'Hijau',
        'orange' => 'Oranye',
        'purple' => 'Ungu',
        'teal' => 'Toska',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'target_amount' => 'decimal:2',
            'contribution_amount' => 'decimal:2',
            'target_date' => 'date:Y-m-d',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'next_period_starts_on' => 'date:Y-m-d',
            'is_recurring' => 'boolean',
            'interval' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetPlanItem::class);
    }
}
