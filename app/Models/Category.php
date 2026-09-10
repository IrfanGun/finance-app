<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'type', 'icon', 'color', 'is_active'])]
class Category extends Model
{
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
