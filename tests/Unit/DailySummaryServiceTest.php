<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use App\Services\DailySummary\DailySummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private DailySummaryService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new DailySummaryService();
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'daily_summary_timezone' => 'Europe/Bucharest',
            'daily_summary_enabled' => true
        ]);
    }

    public function test_returns_null_when_no_relevant_notes(): void
    {
        $summary = $this->service->generateDailySummary($this->user);
        
        $this->assertNull($summary);
    }

    public function test_generates_summary_with_task_due_today(): void
    {
        $today = Carbon::now('Europe/Bucharest');
        
        // Create a task due today
        Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Complete project',
            'is_completed' => false,
            'priority' => 3,
            'metadata' => [
                'due_date' => $today->format('Y-m-d'),
                'due_time' => '14:00'
            ]
        ]);

        $summary = $this->service->generateDailySummary($this->user);
        
        $this->assertNotNull($summary);
        $this->assertStringContainsString('Bună dimineața, Test User!', $summary);
        $this->assertStringContainsString('Task-uri pentru azi', $summary);
        $this->assertStringContainsString('Complete project', $summary);
        $this->assertStringContainsString('🔥', $summary); // High priority icon
    }

    public function test_generates_summary_with_event_today(): void
    {
        $today = Carbon::now('Europe/Bucharest');
        
        // Create an event for today
        Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_EVENT,
            'title' => 'Team meeting',
            'metadata' => [
                'event_date' => $today->format('Y-m-d'),
                'event_time' => '10:00'
            ]
        ]);

        $summary = $this->service->generateDailySummary($this->user);
        
        $this->assertNotNull($summary);
        $this->assertStringContainsString('Evenimente', $summary);
        $this->assertStringContainsString('Team meeting', $summary);
        $this->assertStringContainsString('(10:00)', $summary);
    }

    public function test_generates_summary_with_reminder_today(): void
    {
        $today = Carbon::now('Europe/Bucharest');
        
        // Create a reminder note
        $reminderNote = Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_REMINDER,
            'title' => 'Call dentist'
        ]);

        // Create associated reminder
        Reminder::factory()->create([
            'note_id' => $reminderNote->id,
            'next_remind_at' => $today->setTime(9, 30),
            'is_sent' => false
        ]);

        $summary = $this->service->generateDailySummary($this->user);
        
        $this->assertNotNull($summary);
        $this->assertStringContainsString('Memento-uri', $summary);
        $this->assertStringContainsString('Call dentist', $summary);
        $this->assertStringContainsString('(09:30)', $summary);
    }

    public function test_groups_multiple_note_types(): void
    {
        $today = Carbon::now('Europe/Bucharest');
        
        // Create task
        Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Review code',
            'is_completed' => false,
            'metadata' => ['due_date' => $today->format('Y-m-d')]
        ]);

        // Create event
        Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_EVENT,
            'title' => 'Lunch meeting',
            'metadata' => ['event_date' => $today->format('Y-m-d')]
        ]);

        $summary = $this->service->generateDailySummary($this->user);
        
        $this->assertNotNull($summary);
        $this->assertStringContainsString('Task-uri pentru azi (1)', $summary);
        $this->assertStringContainsString('Evenimente (1)', $summary);
        $this->assertStringContainsString('Review code', $summary);
        $this->assertStringContainsString('Lunch meeting', $summary);
    }

    public function test_excludes_completed_tasks(): void
    {
        $today = Carbon::now('Europe/Bucharest');
        
        // Create completed task
        Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Completed task',
            'is_completed' => true,
            'metadata' => ['due_date' => $today->format('Y-m-d')]
        ]);

        $summary = $this->service->generateDailySummary($this->user);
        
        $this->assertNull($summary);
    }

    public function test_respects_user_timezone(): void
    {
        $userInNewYork = User::factory()->create([
            'name' => 'NYC User',
            'daily_summary_timezone' => 'America/New_York'
        ]);

        $nycToday = Carbon::now('America/New_York');
        
        Note::factory()->create([
            'user_id' => $userInNewYork->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'NYC Task',
            'is_completed' => false,
            'metadata' => ['due_date' => $nycToday->format('Y-m-d')]
        ]);

        $summary = $this->service->generateDailySummary($userInNewYork);
        
        $this->assertNotNull($summary);
        $this->assertStringContainsString('NYC User', $summary);
        $this->assertStringContainsString('NYC Task', $summary);
    }

    public function test_generates_personalized_greeting_based_on_time(): void
    {
        $morningTime = Carbon::create(2024, 1, 15, 8, 0, 0, 'Europe/Bucharest');
        $afternoonTime = Carbon::create(2024, 1, 15, 14, 0, 0, 'Europe/Bucharest');
        $eveningTime = Carbon::create(2024, 1, 15, 20, 0, 0, 'Europe/Bucharest');
        
        // Create a task for testing
        Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Test task',
            'is_completed' => false,
            'metadata' => ['due_date' => $morningTime->format('Y-m-d')]
        ]);

        $morningSummary = $this->service->generateDailySummary($this->user, $morningTime);
        $afternoonSummary = $this->service->generateDailySummary($this->user, $afternoonTime);
        $eveningSummary = $this->service->generateDailySummary($this->user, $eveningTime);
        
        $this->assertStringContainsString('Bună dimineața', $morningSummary);
        $this->assertStringContainsString('Bună ziua', $afternoonSummary);
        $this->assertStringContainsString('Bună seara', $eveningSummary);
    }
}
