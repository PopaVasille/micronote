<?php

namespace App\Models;

use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    protected $fillable = [
        'note_id',
        'next_remind_at',
        'recurrence_rule',
        'recurrence_ends_at',
        'reminder_type',
        'message',
        'is_sent',
    ];

    protected $casts = [
        'next_remind_at' => 'datetime',
        'recurrence_ends_at' => 'datetime',
    ];

    /**
     * Get the note that owns the reminder.
     *
     * @return BelongsTo
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

}
