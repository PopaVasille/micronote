<?php

namespace App\Jobs\Reminders;

use App\Models\Reminder;
use App\Services\Reminders\ReminderDeliveryService;
use App\Services\Reminders\ReminderSchedulingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAndSendReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ?ReminderDeliveryService $deliveryService = null,
        private ?ReminderSchedulingService $schedulingService = null
    ) {}

    public function handle(
        ReminderDeliveryService $deliveryService,
        ReminderSchedulingService $schedulingService
    ): void {
        $deliveryService = $this->deliveryService ?? $deliveryService;
        $schedulingService = $this->schedulingService ?? $schedulingService;

        Log::info('Running ProcessAndSendReminders Job...');

        $reminders = Reminder::where('next_remind_at', '<=', now()->addMinute())
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

        foreach ($reminders as $reminder) {
            try {
                // Check if reminder is too old (more than 24 hours past due)
                if ($reminder->next_remind_at->lt(now()->subDay())) {
                    Log::info("Reminder #{$reminder->id} is more than 24 hours overdue. Marking as expired.");
                    $schedulingService->rescheduleOrDeleteReminder($reminder);

                    continue;
                }

                $sent = $deliveryService->deliverReminder($reminder);

                if ($sent) {
                    $schedulingService->rescheduleOrDeleteReminder($reminder);
                } else {
                    Log::warning("Failed to deliver reminder #{$reminder->id}. Will retry on next job run.");
                }

            } catch (\Exception $e) {
                Log::error("Failed to process reminder #{$reminder->id}: ".$e->getMessage());
            }
        }
    }
}
