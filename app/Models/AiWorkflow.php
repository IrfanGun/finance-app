<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'action', 'status', 'draft', 'missing_fields', 'result', 'idempotency_key', 'version', 'expires_at'])]
class AiWorkflow extends Model
{
    protected function casts(): array
    {
        return [
            'draft' => 'array',
            'missing_fields' => 'array',
            'result' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
