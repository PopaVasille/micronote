<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramService
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('services.telegram-bot-api.token'));
    }

    public function sendReminder(string $chatId, string $reminderMessage): bool
    {
        try {
            $message = "🔔 Reminder: " . $reminderMessage;
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
            ]);

            Log::info("Telegram reminder sent successfully", [
                'chat_id' => $chatId,
                'message_length' => strlen($reminderMessage)
            ]);

            return true;

        } catch (TelegramSDKException $e) {
            Log::error("Telegram SDK error sending reminder", [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Exception sending Telegram reminder", [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Send simple message via Telegram
     *
     * @param string $chatId
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $chatId, string $message): bool
    {
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
            ]);

            Log::info("Telegram message sent successfully", [
                'chat_id' => $chatId,
                'message_length' => strlen($message)
            ]);

            return true;

        } catch (TelegramSDKException $e) {
            Log::error("Telegram SDK error sending message", [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Exception sending Telegram message", [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            return false;
        }
    }
}