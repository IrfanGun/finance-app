<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Category;
use App\Models\FinancialAccount;
#[Fillable(['user_id', 'account_id', 'category_id', 'type', 'amount', 'date', 'title', 'note'])]

class Transaction extends Model
{
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function account(): BelongsTo { return $this->belongsTo(FinancialAccount::class, 'account_id'); }
    //
}
