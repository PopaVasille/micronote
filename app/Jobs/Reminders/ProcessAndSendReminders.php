<?php

namespace App\Jobs\Reminders;

use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ProcessAndSendReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    /**
     * @throws TelegramSDKException
     */
    public function handle(): void
    {
        Log::info('Running ProcessAndSendReminders Job...');

        $reminders = Reminder::where('next_remind_at', '<=', now())
            ->whereHas('note', function ($query) {
                $query->where('is_completed', false);
            })
            ->with('note.user')
            ->get();

        if ($reminders->isEmpty()) {
            Log::info('No reminders to process.');
            return;
        }

        Log::info("Found {$reminders->count()} reminders to process.");
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));

        foreach ($reminders as $reminder) {
            try {
                $user = $reminder->note->user;

                if ($user && $user->telegram_id) {
                    $message = "🔔 Reminder: " . ($reminder->message ?? $reminder->note->content);
                    $telegram->sendMessage([
                        'chat_id' => $user->telegram_id,
                        'text' => $message,
                    ]);
                    Log::info("Sent reminder #{$reminder->id} to user #{$user->id}");
                }

                $this->rescheduleOrDelete($reminder);

            } catch (\Exception $e) {
                Log::error("Failed to process reminder #{$reminder->id}: " . $e->getMessage());
            }
        }
    }

    private function rescheduleOrDelete(Reminder $reminder): void
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
            default => $current->addDay(), // Fallback
        };
    }
}
