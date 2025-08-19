<?php

namespace App\Services\Reminders;

use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderSchedulingService
{
    public function rescheduleOrDeleteReminder(Reminder $reminder): void
    {
        if (!$reminder->recurrence_rule) {
            $reminder->delete();
            Log::info("Deleted single reminder #{$reminder->id}");
            return;
        }

        $nextRemindAt = $this->calculateNextRecurrence($reminder->next_remind_at, $reminder->recurrence_rule);

        if ($reminder->recurrence_ends_at && $nextRemindAt->gt($reminder->recurrence_ends_at)) {
            $reminder->delete();
            Log::info("Deleted finished recurring reminder #{$reminder->id}");
        } else {
            $reminder->update(['next_remind_at' => $nextRemindAt]);
            Log::info("Rescheduled reminder #{$reminder->id} to {$nextRemindAt->toDateTimeString()}");
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