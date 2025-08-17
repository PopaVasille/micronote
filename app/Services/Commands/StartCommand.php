<?php

namespace App\Services\Commands;

use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WhatsAppTextNotification;

/**
 * StartCommand handles the /start command for both Telegram and WhatsApp
 * 
 * This command provides users with their channel-specific ID that they can
 * use to link their account in the web application.
 */
class StartCommand extends AbstractCommand
{
    private ?Api $telegram = null;

    /**
     * Get Telegram API instance (lazy loaded)
     */
    private function getTelegramApi(): Api
    {
        if ($this->telegram === null) {
            $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        }
        return $this->telegram;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        return '/start';
    }

    /**
     * {@inheritdoc}
     */
    public function handle(string $channelType, string $identifier, array $metadata): bool
    {
        $this->logCommandExecution($channelType, $identifier, 'started');

        try {
            switch ($channelType) {
                case 'telegram':
                    return $this->handleTelegramStart($identifier, $metadata);
                
                case 'whatsapp':
                    return $this->handleWhatsAppStart($identifier, $metadata);
                
                default:
                    $this->logCommandExecution($channelType, $identifier, 'failed', [
                        'error' => 'Unsupported channel type'
                    ]);
                    return false;
            }
        } catch (\Exception $e) {
            $this->logCommandExecution($channelType, $identifier, 'failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Handle /start command for Telegram
     * Replicates the exact logic from TelegramBotController
     *
     * @param string $telegramId
     * @param array $metadata
     * @return bool
     */
    private function handleTelegramStart(string $telegramId, array $metadata): bool
    {
        // Validate required metadata from Telegram webhook
        if (!isset($metadata['chat_id'])) {
            Log::warning('Missing required Telegram metadata for /start command', [
                'telegram_id' => $telegramId,
                'metadata' => $metadata,
                'required' => 'chat_id'
            ]);
            return false;
        }

        $chatId = $metadata['chat_id'];
        
        // Build response message (exact same as original)
        $response = "Salut! ID-ul tău Telegram este: $telegramId\n\n";
        $response .= "Copiază acest ID și introdu-l în aplicația MicroNote pentru a conecta contul tău.";

        try {
            // Send message via Telegram API (exact same as original)
            $this->getTelegramApi()->sendMessage([
                'chat_id' => $chatId,
                'text' => $response
            ]);

            $this->logCommandExecution('telegram', $telegramId, 'completed', [
                'chat_id' => $chatId,
                'response_sent' => true
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram /start response', [
                'telegram_id' => $telegramId,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle /start command for WhatsApp
     * Will be implemented when we refactor WhatsApp channel
     *
     * @param string $waId
     * @param array $metadata
     * @return bool
     */
    private function handleWhatsAppStart(string $waId, array $metadata): bool
    {
        // Validate required metadata from WhatsApp webhook
        if (!isset($metadata['phone_number'])) {
            Log::warning('Missing phone_number for WhatsApp /start command', [
                'wa_id' => $waId,
                'metadata' => $metadata
            ]);
            return false;
        }

        $phoneNumber = $metadata['phone_number'];

        try {
            // Build WhatsApp response message
            $message = "🔗 ID-ul tău WhatsApp pentru conectare este: *{$waId}*\n\n";
            $message .= "Accesează aplicația web și introdu acest ID în pagina de conectare WhatsApp.";

            // Send WhatsApp notification
            Notification::route('whatsapp', $phoneNumber)
                ->notify(new WhatsAppTextNotification($message));

            $this->logCommandExecution('whatsapp', $waId, 'completed', [
                'phone_number' => $phoneNumber,
                'response_sent' => true
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp /start response', [
                'wa_id' => $waId,
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}