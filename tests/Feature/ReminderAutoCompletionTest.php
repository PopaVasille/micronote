<?php

namespace Tests\Feature;

use App\Jobs\Reminders\ProcessAndSendReminders;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use App\Services\Reminders\ReminderSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderAutoCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_single_reminder_marks_note_as_completed_when_deleted(): void
    {
        // Create a note with a single (non-recurring) reminder
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);

        $reminder = Reminder::factory()->create([
            'note_id' => $note->id,
            'next_remind_at' => now()->subMinute(),
            'recurrence_rule' => null, // Single reminder
        ]);

        // Simulate processing the reminder
        $schedulingService = new ReminderSchedulingService;
        $schedulingService->rescheduleOrDeleteReminder($reminder);

        // Assert the note is marked as completed
        $note->refresh();
        $this->assertTrue($note->is_completed);

        // Assert the reminder is deleted
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }

    public function test_recurring_reminder_marks_note_as_completed_when_recurrence_ends(): void
    {
        // Create a note with a recurring reminder that has ended
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);

        $reminder = Reminder::factory()->create([
            'note_id' => $note->id,
            'next_remind_at' => now()->subDay(),
            'recurrence_rule' => 'DAILY',
            'recurrence_ends_at' => now()->subHour(), // Already ended
        ]);

        // Simulate processing the reminder
        $schedulingService = new ReminderSchedulingService;
        $schedulingService->rescheduleOrDeleteReminder($reminder);

        // Assert the note is marked as completed
        $note->refresh();
        $this->assertTrue($note->is_completed);

        // Assert the reminder is deleted
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }

    public function test_recurring_reminder_reschedules_when_still_active(): void
    {
        // Create a note with an active recurring reminder
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);

        $originalTime = now()->subMinute();
        $reminder = Reminder::factory()->create([
            'note_id' => $note->id,
            'next_remind_at' => $originalTime,
            'recurrence_rule' => 'DAILY',
            'recurrence_ends_at' => now()->addWeek(), // Still active
        ]);

        // Simulate processing the reminder
        $schedulingService = new ReminderSchedulingService;
        $schedulingService->rescheduleOrDeleteReminder($reminder);

        // Assert the note is NOT marked as completed
        $note->refresh();
        $this->assertFalse($note->is_completed);

        // Assert the reminder is rescheduled (not deleted)
        $reminder->refresh();
        $this->assertTrue($reminder->next_remind_at->gt($originalTime));
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id]);
    }

    public function test_overdue_reminder_is_marked_as_expired_by_job(): void
    {
        // Create a note with an overdue reminder (more than 24 hours old)
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'is_completed' => false,
        ]);

        $reminder = Reminder::factory()->create([
            'note_id' => $note->id,
            'next_remind_at' => now()->subDays(2), // 2 days overdue
            'recurrence_rule' => null,
        ]);

        // Create a mock delivery service that would return false (can't deliver)
        $deliveryService = $this->createMock(\App\Services\Reminders\ReminderDeliveryService::class);
        $deliveryService->expects($this->never())
            ->method('deliverReminder');

        $schedulingService = new ReminderSchedulingService;

        // Simulate the job processing
        $job = new ProcessAndSendReminders($deliveryService, $schedulingService);
        $job->handle($deliveryService, $schedulingService);

        // Assert the note is marked as completed due to being overdue
        $note->refresh();
        $this->assertTrue($note->is_completed);

        // Assert the reminder is deleted
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }

    public function test_does_not_mark_already_completed_note(): void
    {
        // Create a note that is already completed
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'is_completed' => true, // Already completed
        ]);

        $reminder = Reminder::factory()->create([
            'note_id' => $note->id,
            'next_remind_at' => now()->subMinute(),
            'recurrence_rule' => null,
        ]);

        // Simulate processing the reminder
        $schedulingService = new ReminderSchedulingService;
        $schedulingService->rescheduleOrDeleteReminder($reminder);

        // Note should remain completed (no change)
        $note->refresh();
        $this->assertTrue($note->is_completed);

        // Reminder should still be deleted
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }
}
