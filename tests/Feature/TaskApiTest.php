<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_active_tasks_returns_tasks_with_due_date_today()
    {
        $user = User::factory()->create();
        $today = Carbon::now()->format('Y-m-d');
        
        // Create a task with due date today
        $task = Note::factory()->create([
            'user_id' => $user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Task due today',
            'is_completed' => false,
            'priority' => 2,
            'metadata' => [
                'due_date' => $today,
                'due_time' => '14:00'
            ]
        ]);

        $response = $this->actingAs($user)->getJson('/api/tasks/active');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'tasks_with_due_date',
                    'tasks_without_due_date',
                    'total_count'
                ])
                ->assertJsonPath('tasks_with_due_date.0.id', $task->id)
                ->assertJsonPath('tasks_with_due_date.0.title', 'Task due today')
                ->assertJsonPath('total_count', 1);
    }

    public function test_get_active_tasks_returns_tasks_without_due_date()
    {
        $user = User::factory()->create();
        
        // Create a task without due date
        $task = Note::factory()->create([
            'user_id' => $user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Task without due date',
            'is_completed' => false,
            'priority' => 1,
            'metadata' => []
        ]);

        $response = $this->actingAs($user)->getJson('/api/tasks/active');

        $response->assertStatus(200)
                ->assertJsonPath('tasks_without_due_date.0.id', $task->id)
                ->assertJsonPath('tasks_without_due_date.0.title', 'Task without due date')
                ->assertJsonPath('tasks_without_due_date.0.due_date', null)
                ->assertJsonPath('total_count', 1);
    }

    public function test_get_active_tasks_excludes_completed_tasks()
    {
        $user = User::factory()->create();
        $today = Carbon::now()->format('Y-m-d');
        
        // Create completed task
        Note::factory()->create([
            'user_id' => $user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Completed task',
            'is_completed' => true,
            'metadata' => ['due_date' => $today]
        ]);

        $response = $this->actingAs($user)->getJson('/api/tasks/active');

        $response->assertStatus(200)
                ->assertJsonPath('total_count', 0);
    }

    public function test_complete_task_marks_task_as_completed()
    {
        $user = User::factory()->create();
        
        $task = Note::factory()->create([
            'user_id' => $user->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'Task to complete',
            'is_completed' => false
        ]);

        $response = $this->actingAs($user)->post("/api/tasks/{$task->id}/complete");

        $response->assertStatus(302) // Redirect back
                ->assertSessionHas('success', 'Task marcat ca finalizat!');

        $this->assertTrue($task->fresh()->is_completed);
    }

    public function test_complete_task_fails_for_non_existent_task()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/tasks/999/complete');

        $response->assertStatus(302) // Redirect back
                ->assertSessionHasErrors(['message']);
    }

    public function test_complete_task_fails_for_other_users_task()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $task = Note::factory()->create([
            'user_id' => $user1->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => false
        ]);

        $response = $this->actingAs($user2)->post("/api/tasks/{$task->id}/complete");

        $response->assertStatus(302); // Redirect back
        $this->assertFalse($task->fresh()->is_completed);
    }

    public function test_get_active_tasks_only_returns_user_tasks()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $today = Carbon::now()->format('Y-m-d');
        
        // Create task for user1
        Note::factory()->create([
            'user_id' => $user1->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'User 1 task',
            'is_completed' => false,
            'metadata' => ['due_date' => $today]
        ]);

        // Create task for user2
        Note::factory()->create([
            'user_id' => $user2->id,
            'note_type' => Note::TYPE_TASK,
            'title' => 'User 2 task',
            'is_completed' => false,
            'metadata' => ['due_date' => $today]
        ]);

        $response = $this->actingAs($user1)->getJson('/api/tasks/active');

        $response->assertStatus(200)
                ->assertJsonPath('total_count', 1)
                ->assertJsonPath('tasks_with_due_date.0.title', 'User 1 task');
    }
}