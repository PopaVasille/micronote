<?php

namespace Tests\Unit\Services\Commands;

use App\Services\Commands\StartCommand;
use PHPUnit\Framework\TestCase;

/**
 * Test StartCommand functionality
 */
class StartCommandTest extends TestCase
{
    private StartCommand $startCommand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startCommand = new StartCommand();
    }

    public function test_get_command_returns_start(): void
    {
        $this->assertEquals('/start', $this->startCommand->getCommand());
    }

    public function test_supports_telegram_channel(): void
    {
        $this->assertTrue($this->startCommand->supportsChannel('telegram'));
    }

    public function test_supports_whatsapp_channel(): void
    {
        $this->assertTrue($this->startCommand->supportsChannel('whatsapp'));
    }

    public function test_does_not_support_invalid_channel(): void
    {
        $this->assertFalse($this->startCommand->supportsChannel('invalid'));
    }
}