<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'memory_key', 'value', 'source_message_id', 'expires_at'])]
class AiUserMemory extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'source_message_id');
    }
}
