<?php

namespace App\Services\Reminders;

use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderSchedulingService
{
    public function rescheduleOrDeleteReminder(Reminder $reminder): void
    {
        if (! $reminder->recurrence_rule) {
            // Mark the associated note as completed before deleting the reminder
            $this->markNoteAsCompleted($reminder);
            $reminder->delete();
            Log::info("Marked note as completed and deleted single reminder #{$reminder->id}");

            return;
        }

        $nextRemindAt = $this->calculateNextRecurrence($reminder->next_remind_at, $reminder->recurrence_rule);

        if ($reminder->recurrence_ends_at && $nextRemindAt->gt($reminder->recurrence_ends_at)) {
            // Mark the associated note as completed before deleting the reminder
            $this->markNoteAsCompleted($reminder);
            $reminder->delete();
            Log::info("Marked note as completed and deleted finished recurring reminder #{$reminder->id}");
        } else {
            $reminder->update(['next_remind_at' => $nextRemindAt]);
            Log::info("Rescheduled reminder #{$reminder->id} to {$nextRemindAt->toDateTimeString()}");
        }
    }

    /**
     * Mark the associated note as completed when a reminder expires
     */
    private function markNoteAsCompleted(Reminder $reminder): void
    {
        $note = $reminder->note;

        if ($note && ! $note->is_completed) {
            $note->update(['is_completed' => true]);
            Log::info("Marked note #{$note->id} as completed due to expired reminder");
        }
    }

    private function calculateNextRecurrence(Carbon $current, string $rule): Carbon
    {
        return match (strtoupper($rule)) {
            'WEEKLY' => $current->addWeek(),
            'MONTHLY' => $current->addMonth(),
            'YEARLY' => $current->addYear(),
            default => $current->addDay(),
        };
    }
}
