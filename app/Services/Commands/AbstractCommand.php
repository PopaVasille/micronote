<?php

namespace App\Services\Commands;

use App\Services\Commands\Contracts\CommandInterface;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for messaging commands
 * 
 * Provides common functionality and logging for all command implementations.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * List of supported channels for this command
     * Override in subclasses to restrict channel support
     */
    protected array $supportedChannels = ['telegram', 'whatsapp'];

    /**
     * {@inheritdoc}
     */
    public function supportsChannel(string $channelType): bool
    {
        return in_array($channelType, $this->supportedChannels);
    }

    /**
     * Log command execution for debugging and monitoring
     *
     * @param string $channelType
     * @param string $identifier
     * @param string $status
     * @param array $context
     * @return void
     */
    protected function logCommandExecution(
        string $channelType, 
        string $identifier, 
        string $status, 
        array $context = []
    ): void {
        Log::channel('trace')->info("Command {$this->getCommand()} {$status}", [
            'channel' => $channelType,
            'identifier' => $identifier,
            'command' => $this->getCommand(),
            ...$context
        ]);
    }

    /**
     * Validate required data from webhook metadata
     *
     * @param array $metadata
     * @param array $requiredKeys
     * @return bool
     */
    protected function validateMetadata(array $metadata, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!isset($metadata[$key])) {
                return false;
            }
        }
        return true;
    }
}