<?php

namespace Tests\Unit\Services\Commands;

use App\Services\Commands\CommandProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Test CommandProcessor functionality
 */
class CommandProcessorTest extends TestCase
{
    private CommandProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new CommandProcessor();
    }

    public function test_identifies_start_command(): void
    {
        $this->assertTrue($this->processor->isCommand('/start'));
    }

    public function test_identifies_regular_message_as_not_command(): void
    {
        $this->assertFalse($this->processor->isCommand('Hello world'));
    }

    public function test_ignores_slash_in_middle_of_message(): void
    {
        $this->assertFalse($this->processor->isCommand('Check this /link out'));
    }

    public function test_start_command_is_registered(): void
    {
        $commands = $this->processor->getRegisteredCommands();
        $this->assertTrue($commands->has('/start'));
    }
}