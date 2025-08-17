<?php

namespace App\Services\Commands;

use App\Services\Commands\Contracts\CommandInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * CommandProcessor handles the routing and execution of messaging commands
 * 
 * This service acts as a dispatcher that identifies commands in messages
 * and routes them to the appropriate command handlers.
 */
class CommandProcessor
{
    /**
     * Collection of registered command handlers
     */
    private Collection $commands;

    /**
     * CommandProcessor constructor
     * Automatically registers all available commands
     */
    public function __construct()
    {
        $this->commands = collect();
        $this->registerCommands();
    }

    /**
     * Process a message and execute command if found
     *
     * @param string $messageContent The full message content
     * @param string $channelType The messaging channel ('telegram' or 'whatsapp')
     * @param string $identifier The user identifier (telegram_id or wa_id)
     * @param array $metadata Additional webhook data
     * @return bool True if a command was found and executed
     */
    public function process(
        string $messageContent, 
        string $channelType, 
        string $identifier, 
        array $metadata
    ): bool {
        Log::channel('trace')->info('CommandProcessor: Processing potential command', [
            'channel' => $channelType,
            'identifier' => $identifier,
            'message' => $messageContent
        ]);

        // Extract command from message
        $command = $this->extractCommand($messageContent);
        
        if (!$command) {
            Log::channel('trace')->debug('CommandProcessor: No command found in message', [
                'channel' => $channelType,
                'identifier' => $identifier,
                'message' => $messageContent
            ]);
            return false;
        }

        // Find handler for the command
        $handler = $this->findHandler($command, $channelType);
        
        if (!$handler) {
            Log::channel('trace')->warning('CommandProcessor: No handler found for command', [
                'channel' => $channelType,
                'identifier' => $identifier,
                'command' => $command
            ]);
            return false;
        }

        // Execute command
        try {
            Log::channel('trace')->info('CommandProcessor: Executing command', [
                'channel' => $channelType,
                'identifier' => $identifier,
                'command' => $command,
                'handler' => get_class($handler)
            ]);

            $result = $handler->handle($channelType, $identifier, $metadata);

            Log::channel('trace')->info('CommandProcessor: Command execution completed', [
                'channel' => $channelType,
                'identifier' => $identifier,
                'command' => $command,
                'success' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::channel('trace')->error('CommandProcessor: Command execution failed', [
                'channel' => $channelType,
                'identifier' => $identifier,
                'command' => $command,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check if a message contains a command
     *
     * @param string $messageContent
     * @return bool
     */
    public function isCommand(string $messageContent): bool
    {
        return $this->extractCommand($messageContent) !== null;
    }

    /**
     * Register a new command handler
     *
     * @param CommandInterface $command
     * @return void
     */
    public function registerCommand(CommandInterface $command): void
    {
        $this->commands->put($command->getCommand(), $command);
        
        Log::channel('trace')->debug('CommandProcessor: Registered command', [
            'command' => $command->getCommand(),
            'handler' => get_class($command)
        ]);
    }

    /**
     * Get all registered commands
     *
     * @return Collection
     */
    public function getRegisteredCommands(): Collection
    {
        return $this->commands;
    }

    /**
     * Extract command from message content
     * Commands are expected to start with '/' and be at the beginning of the message
     *
     * @param string $messageContent
     * @return string|null
     */
    private function extractCommand(string $messageContent): ?string
    {
        $trimmedMessage = trim($messageContent);
        
        // Check if message starts with '/'
        if (!str_starts_with($trimmedMessage, '/')) {
            return null;
        }

        // Extract command (first word)
        $parts = explode(' ', $trimmedMessage, 2);
        $command = $parts[0];

        // Validate command format
        if (preg_match('/^\/[a-zA-Z0-9_]+$/', $command)) {
            return $command;
        }

        return null;
    }

    /**
     * Find appropriate handler for command and channel
     *
     * @param string $command
     * @param string $channelType
     * @return CommandInterface|null
     */
    private function findHandler(string $command, string $channelType): ?CommandInterface
    {
        $handler = $this->commands->get($command);
        
        if (!$handler) {
            return null;
        }

        // Check if handler supports the channel
        if (!$handler->supportsChannel($channelType)) {
            Log::channel('trace')->warning('CommandProcessor: Handler does not support channel', [
                'command' => $command,
                'channel' => $channelType,
                'handler' => get_class($handler)
            ]);
            return null;
        }

        return $handler;
    }

    /**
     * Register all available command handlers
     * Add new commands here as they are implemented
     *
     * @return void
     */
    private function registerCommands(): void
    {
        // Register StartCommand
        $this->registerCommand(new StartCommand());
        
        // Future commands will be registered here:
        // $this->registerCommand(new HelpCommand());
        // $this->registerCommand(new StatsCommand());
        
        Log::channel('trace')->info('CommandProcessor: All commands registered', [
            'total_commands' => $this->commands->count(),
            'commands' => $this->commands->keys()->toArray()
        ]);
    }
}