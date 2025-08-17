<?php

namespace App\Services\Commands\Contracts;

/**
 * Interface for handling messaging commands (/start, /help, etc.)
 * 
 * This interface defines the contract for command handlers that can
 * process commands from different messaging channels (Telegram, WhatsApp).
 */
interface CommandInterface
{
    /**
     * Handle the command execution
     *
     * @param string $channelType The messaging channel ('telegram' or 'whatsapp')
     * @param string $identifier The user identifier (telegram_id or wa_id)
     * @param array $metadata Additional context data from the webhook
     * @return bool Success status of command execution
     */
    public function handle(string $channelType, string $identifier, array $metadata): bool;

    /**
     * Get the command string this handler responds to
     *
     * @return string The command (e.g., '/start', '/help')
     */
    public function getCommand(): string;

    /**
     * Check if this command is supported for the given channel
     *
     * @param string $channelType The messaging channel
     * @return bool Whether the command is supported
     */
    public function supportsChannel(string $channelType): bool;
}