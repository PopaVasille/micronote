<?php

namespace Tests\Feature\Feature;

use App\Models\Note;
use App\Models\User;
use App\Services\Messaging\UnifiedMessageProcessorService;
use App\Services\Classification\GeminiClassificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PremiumMessageProcessingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private UnifiedMessageProcessorService $processor;
    private GeminiClassificationService $mockGeminiService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock GeminiClassificationService for consistent testing
        $this->mockGeminiService = Mockery::mock(GeminiClassificationService::class);
        $this->app->instance(GeminiClassificationService::class, $this->mockGeminiService);
        
        $this->processor = $this->app->make(UnifiedMessageProcessorService::class);
    }

    /**
     * Test Premium user processes multiple actions successfully
     */
    public function test_premium_user_processes_multiple_actions_successfully(): void
    {
        // Create a Premium user
        $user = User::factory()->create([
            'telegram_id' => '12345',
            'plan' => 'plus'
        ]);
        
        $this->assertTrue($user->isPremium());

        // Mock the multi-action response from Gemini
        $mockResponse = [
            'reminders' => [
                [
                    'message' => 'să o suni pe mama',
                    'remind_at' => '2025-09-06 12:00:00'
                ]
            ],
            'tasks' => [
                [
                    'title' => 'termină raportul'
                ]
            ],
            'shopping_list' => [
                'title' => 'Lista de cumpărături',
                'items' => [
                    ['text' => 'lapte', 'completed' => false],
                    ['text' => 'pâine', 'completed' => false]
                ]
            ]
        ];

        $this->mockGeminiService
            ->shouldReceive('extractMultipleActions')
            ->once()
            ->with('nu uita sa o suni pe mama maine la 12, trebuie sa termin raportul, cumpar lapte si paine')
            ->andReturn($mockResponse);

        // Process the message
        $result = $this->processor->processMessage(
            'telegram',
            '12345',
            'nu uita sa o suni pe mama maine la 12, trebuie sa termin raportul, cumpar lapte si paine',
            ['message' => ['date' => time()]],
            'test-correlation-id'
        );

        $this->assertNotNull($result);
        
        // Verify notes were created
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'note_type' => Note::TYPE_REMINDER,
            'content' => 'să o suni pe mama'
        ]);
        
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'note_type' => Note::TYPE_TASK,
            'content' => 'termină raportul'
        ]);
        
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'note_type' => Note::TYPE_SHOPING_LIST,
            'title' => 'Lista de cumpărături'
        ]);

        // Verify user stats updated
        $user->refresh();
        $this->assertEquals(3, $user->notes_count);
    }

    /**
     * Test Free user processes only single action
     */
    public function test_free_user_processes_single_action_only(): void
    {
        // Create a Free user
        $user = User::factory()->create([
            'telegram_id' => '54321',
            'plan' => 'free'
        ]);
        
        $this->assertFalse($user->isPremium());

        // The service will use HybridMessageClassificationService for Free users
        // No need to mock extractMultipleActions as it won't be called
        
        // Process the same complex message
        $result = $this->processor->processMessage(
            'telegram',
            '54321',
            'nu uita sa o suni pe mama maine la 12, trebuie sa termin raportul, cumpar lapte si paine',
            ['message' => ['date' => time()]],
            'test-correlation-id-free'
        );

        $this->assertNotNull($result);
        
        // Verify only one note was created (Free behavior)
        $this->assertEquals(1, Note::where('user_id', $user->id)->count());
        
        // Verify user stats updated with single note
        $user->refresh();
        $this->assertEquals(1, $user->notes_count);
    }

    /**
     * Test Premium fallback when AI processing fails
     */
    public function test_premium_fallback_when_ai_processing_fails(): void
    {
        // Create a Premium user
        $user = User::factory()->create([
            'telegram_id' => '67890',
            'plan' => 'plus'
        ]);

        // Mock AI failure
        $this->mockGeminiService
            ->shouldReceive('extractMultipleActions')
            ->once()
            ->andReturn(null); // Simulate AI failure

        // Process the message
        $result = $this->processor->processMessage(
            'telegram',
            '67890',
            'test message that should trigger fallback',
            ['message' => ['date' => time()]],
            'test-correlation-id-fallback'
        );

        $this->assertNotNull($result);
        
        // Verify fallback to Free behavior - one note created
        $this->assertEquals(1, Note::where('user_id', $user->id)->count());
        
        // Verify user stats updated
        $user->refresh();
        $this->assertEquals(1, $user->notes_count);
    }

    /**
     * Test isPremium method on User model
     */
    public function test_user_is_premium_method(): void
    {
        $freeUser = User::factory()->create(['plan' => 'free']);
        $premiumUser = User::factory()->create(['plan' => 'plus']);
        
        $this->assertFalse($freeUser->isPremium());
        $this->assertTrue($premiumUser->isPremium());
    }

    /**
     * Test Premium message processing with different action types
     */
    public function test_premium_processes_various_action_types(): void
    {
        $user = User::factory()->create([
            'telegram_id' => '99999',
            'plan' => 'plus'
        ]);

        $mockResponse = [
            'ideas' => [
                [
                    'title' => 'Idee pentru aplicație',
                    'content' => 'aplicatie mobila pentru notite'
                ]
            ],
            'contacts' => [
                [
                    'name' => 'Ion Popescu',
                    'phone' => '0712345678',
                    'email' => 'ion@example.com'
                ]
            ],
            'events' => [
                [
                    'title' => 'Întâlnire cu echipa',
                    'date' => '2025-09-06 14:00:00',
                    'location' => 'Biroul central'
                ]
            ]
        ];

        $this->mockGeminiService
            ->shouldReceive('extractMultipleActions')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->processor->processMessage(
            'telegram',
            '99999',
            'am o idee pentru aplicatie mobila, contactul lui Ion Popescu 0712345678, intalnire cu echipa maine la 14',
            ['message' => ['date' => time()]],
            'test-various-types'
        );

        $this->assertNotNull($result);
        
        // Verify different note types were created
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'note_type' => Note::TYPE_IDEA,
            'title' => 'Idee pentru aplicație'
        ]);
        
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'note_type' => Note::TYPE_CONTACT,
            'title' => 'Ion Popescu'
        ]);
        
        $this->assertDatabaseHas('notes', [
            'user_id' => $user->id,
            'note_type' => Note::TYPE_EVENT,
            'title' => 'Întâlnire cu echipa'
        ]);

        $this->assertEquals(3, Note::where('user_id', $user->id)->count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
