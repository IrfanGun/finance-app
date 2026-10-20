<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recurring_reminder_id',
    'scheduled_at',
    'status',
    'notified_at',
    'snoozed_until',
    'transaction_id',
])]
class RecurringReminderLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
            'snoozed_until' => 'immutable_datetime',
        ];
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(RecurringReminder::class, 'recurring_reminder_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
