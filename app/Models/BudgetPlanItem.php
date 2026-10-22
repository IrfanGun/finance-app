<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['budget_plan_id', 'name', 'amount', 'category_id', 'sort_order'])]
class BudgetPlanItem extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class, 'budget_plan_id');
    }
}
