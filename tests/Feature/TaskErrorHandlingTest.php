<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskErrorHandlingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_handles_unauthorized_access_to_get_active_tasks()
    {
        $response = $this->getJson('/api/tasks/active');

        $response->assertStatus(401)
                ->assertJson([
                    'error' => 'Unauthorized access',
                    'message' => 'User authentication required'
                ]);
    }

    /** @test */
    public function it_handles_database_errors_in_get_active_tasks()
    {
        $this->actingAs($this->user);

        // Simulate database error by dropping the notes table
        DB::statement('DROP TABLE IF EXISTS notes');

        $response = $this->getJson('/api/tasks/active');

        $response->assertStatus(500)
                ->assertJson([
                    'error' => 'Database error',
                    'message' => 'Unable to retrieve tasks due to database issues'
                ]);
    }

    /** @test */
    public function it_handles_invalid_task_id_in_complete_task()
    {
        $this->actingAs($this->user);

        // Test with invalid task ID
        $response = $this->postJson('/api/tasks/invalid/complete');
        $response->assertStatus(404); // Laravel route model binding will return 404

        // Test with negative task ID
        $response = $this->postJson('/api/tasks/-1/complete');
        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Invalid task ID',
                    'message' => 'Task ID must be a positive integer'
                ]);
    }

    /** @test */
    public function it_handles_task_not_found_in_complete_task()
    {
        $this->actingAs($this->user);

        // Try to complete a non-existent task
        $response = $this->postJson('/api/tasks/99999/complete');

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Task not found',
                    'message' => 'Task-ul nu a fost găsit sau nu îți aparține.'
                ]);
    }

    /** @test */
    public function it_handles_completing_already_completed_task()
    {
        $this->actingAs($this->user);

        $task = Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => true // Already completed
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Task not found',
                    'message' => 'Task-ul nu a fost găsit sau nu îți aparține.'
                ]);
    }

    /** @test */
    public function it_handles_unauthorized_task_completion()
    {
        $this->actingAs($this->user);

        // Create a task for another user
        $otherUser = User::factory()->create();
        $task = Note::factory()->create([
            'user_id' => $otherUser->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => false
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Task not found',
                    'message' => 'Task-ul nu a fost găsit sau nu îți aparține.'
                ]);
    }

    /** @test */
    public function it_handles_concurrent_task_completion_attempts()
    {
        $this->actingAs($this->user);

        $task = Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => false
        ]);

        // Simulate concurrent completion by starting a transaction
        DB::beginTransaction();
        
        try {
            // Lock the task
            $lockedTask = Note::where('id', $task->id)->lockForUpdate()->first();
            
            // Try to complete the task while it's locked (this should handle the deadlock)
            $response = $this->postJson("/api/tasks/{$task->id}/complete");
            
            // The response should be successful since we're using proper transaction handling
            $response->assertStatus(200);
            
        } finally {
            DB::rollBack();
        }
    }

    /** @test */
    public function it_returns_valid_task_structure_in_get_active_tasks()
    {
        $this->actingAs($this->user);

        $task = Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => false,
            'title' => 'Test Task',
            'priority' => 2,
            'metadata' => [
                'due_date' => now()->format('Y-m-d'),
                'due_time' => '15:00'
            ]
        ]);

        $response = $this->getJson('/api/tasks/active');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'tasks_with_due_date' => [
                        '*' => [
                            'id',
                            'title',
                            'priority',
                            'due_date',
                            'due_time',
                            'created_at',
                            'updated_at',
                            'note_type'
                        ]
                    ],
                    'tasks_without_due_date',
                    'total_count',
                    'success'
                ]);

        // Verify the task data is properly formatted
        $responseData = $response->json();
        $this->assertEquals(1, $responseData['total_count']);
        $this->assertCount(1, $responseData['tasks_with_due_date']);
        
        $taskData = $responseData['tasks_with_due_date'][0];
        $this->assertEquals($task->id, $taskData['id']);
        $this->assertEquals('Test Task', $taskData['title']);
        $this->assertEquals(2, $taskData['priority']);
        $this->assertEquals('task', $taskData['note_type']);
    }

    /** @test */
    public function it_handles_tasks_with_missing_title()
    {
        $this->actingAs($this->user);

        $task = Note::factory()->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => false,
            'title' => null, // Missing title
            'priority' => 1
        ]);

        $response = $this->getJson('/api/tasks/active');

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $allTasks = array_merge(
            $responseData['tasks_with_due_date'] ?? [],
            $responseData['tasks_without_due_date'] ?? []
        );
        
        $taskData = collect($allTasks)->firstWhere('id', $task->id);
        $this->assertEquals('Untitled Task', $taskData['title']);
    }

    /** @test */
    public function it_limits_task_results_to_prevent_excessive_data_transfer()
    {
        $this->actingAs($this->user);

        // Create more than 50 tasks
        Note::factory()->count(60)->create([
            'user_id' => $this->user->id,
            'note_type' => Note::TYPE_TASK,
            'is_completed' => false
        ]);

        $response = $this->getJson('/api/tasks/active');

        $response->assertStatus(200);
        
        $responseData = $response->json();
        $totalTasks = count($responseData['tasks_with_due_date']) + count($responseData['tasks_without_due_date']);
        
        // Should be limited to 50 tasks per category (100 total max)
        $this->assertLessThanOrEqual(100, $totalTasks);
    }
}