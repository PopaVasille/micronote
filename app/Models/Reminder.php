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

    public mixed $recurrence_rule;
    public mixed $next_remind_at;
    public mixed $recurrence_ends_at;
    protected $fillable = [
        'note_id',
        'next_remind_at',
        'recurrence_rule',
        'recurrence_ends_at',
        'reminder_type',
        'message',
        'is_sent',
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
