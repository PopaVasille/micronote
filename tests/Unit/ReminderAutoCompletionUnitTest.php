<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Reminder;
use App\Services\Reminders\ReminderSchedulingService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ReminderAutoCompletionUnitTest extends TestCase
{
    public function test_marks_note_as_completed_method_works_correctly(): void
    {
        // Create a simple note (avoiding complex ENUM migrations)
        $note = new Note;
        $note->id = 1;
        $note->is_completed = false;
        $note->title = 'Test Note';
        $note->content = 'Test content';
        $note->note_type = 'simple';
        $note->user_id = 1;

        // Mock the reminder
        $reminder = new Reminder;
        $reminder->id = 1;
        $reminder->note_id = 1;

        // Mock the note relationship
        $reminder->setRelation('note', $note);

        $schedulingService = new ReminderSchedulingService;

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($schedulingService);
        $method = $reflection->getMethod('markNoteAsCompleted');
        $method->setAccessible(true);

        // Create a partial mock of the note to test the update call
        $noteMock = $this->createMock(Note::class);
        $noteMock->expects($this->once())
            ->method('update')
            ->with(['is_completed' => true]);

        $noteMock->is_completed = false;

        $reminderMock = $this->createMock(Reminder::class);
        $reminderMock->expects($this->once())
            ->method('getAttribute')
            ->with('note')
            ->willReturn($noteMock);

        // Call the private method
        $method->invoke($schedulingService, $reminderMock);
    }

    public function test_calculate_next_recurrence_works_correctly(): void
    {
        $schedulingService = new ReminderSchedulingService;
        $reflection = new \ReflectionClass($schedulingService);
        $method = $reflection->getMethod('calculateNextRecurrence');
        $method->setAccessible(true);

        $baseTime = Carbon::parse('2025-01-01 10:00:00');

        // Test DAILY
        $result = $method->invoke($schedulingService, $baseTime->copy(), 'DAILY');
        $this->assertEquals('2025-01-02 10:00:00', $result->toDateTimeString());

        // Test WEEKLY
        $result = $method->invoke($schedulingService, $baseTime->copy(), 'WEEKLY');
        $this->assertEquals('2025-01-08 10:00:00', $result->toDateTimeString());

        // Test MONTHLY
        $result = $method->invoke($schedulingService, $baseTime->copy(), 'MONTHLY');
        $this->assertEquals('2025-02-01 10:00:00', $result->toDateTimeString());

        // Test YEARLY
        $result = $method->invoke($schedulingService, $baseTime->copy(), 'YEARLY');
        $this->assertEquals('2026-01-01 10:00:00', $result->toDateTimeString());

        // Test default (should be DAILY)
        $result = $method->invoke($schedulingService, $baseTime->copy(), 'UNKNOWN');
        $this->assertEquals('2025-01-02 10:00:00', $result->toDateTimeString());
    }

    public function test_reschedule_logic_for_single_reminder(): void
    {
        $schedulingService = new ReminderSchedulingService;

        // Mock reminder without recurrence rule
        $reminderMock = $this->createMock(Reminder::class);
        $reminderMock->expects($this->once())
            ->method('getAttribute')
            ->with('recurrence_rule')
            ->willReturn(null);

        // Expect delete to be called
        $reminderMock->expects($this->once())
            ->method('delete');

        // Mock the note relationship
        $noteMock = $this->createMock(Note::class);
        $noteMock->is_completed = false;
        $noteMock->expects($this->once())
            ->method('update')
            ->with(['is_completed' => true]);

        $reminderMock->expects($this->once())
            ->method('getAttribute')
            ->with('note')
            ->willReturn($noteMock);

        // Execute
        $schedulingService->rescheduleOrDeleteReminder($reminderMock);
    }
}
