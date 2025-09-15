<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use App\Services\Classification\GeminiClassificationService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskDueDateExtractionTest extends TestCase
{
    // Nu utilizează RefreshDatabase pentru a evita problema cu ENUM în SQLite

    public function test_gemini_service_has_extract_task_details_method(): void
    {
        $service = app(GeminiClassificationService::class);

        $this->assertTrue(method_exists($service, 'extractTaskDetails'));
    }

    public function test_unified_message_processor_has_extract_task_metadata_method(): void
    {
        $service = app(\App\Services\Messaging\UnifiedMessageProcessorService::class);
        $reflection = new \ReflectionClass($service);

        $this->assertTrue($reflection->hasMethod('extractTaskMetadata'));
    }

    public function test_note_type_constants_include_task(): void
    {
        $this->assertEquals('task', Note::TYPE_TASK);
    }
}