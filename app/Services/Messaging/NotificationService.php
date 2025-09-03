<?php

namespace App\Services\Messaging;

use App\Models\Note;
use App\Services\Telegram\TelegramReminderService;
use App\Services\WhatsApp\WhatsAppReminderService;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService handles sending confirmation messages
 * to users after note creation across different messaging platforms
 */
class NotificationService
{
    public function __construct(
        private readonly TelegramReminderService $telegramService,
        private readonly WhatsAppReminderService $whatsAppService
    ) {}

    /**
     * Send note creation confirmation to user
     *
     * @param string $channelType The messaging channel ('telegram' or 'whatsapp')
     * @param string $identifier The user identifier (telegram_id or wa_id)
     * @param Note $note The created note
     * @param string $correlationId Tracking ID for logging
     * @return bool Success status
     */
    public function sendNoteCreationConfirmation(
        string $channelType,
        string $identifier,
        Note $note,
        string $correlationId
    ): bool {
        $logContext = [
            'correlation_id' => $correlationId,
            'channel' => $channelType,
            'identifier' => $identifier,
            'note_id' => $note->id,
            'note_type' => $note->note_type
        ];

        Log::channel('trace')->info('NotificationService: Sending note creation confirmation', $logContext);

        try {
            $confirmationMessage = $this->buildConfirmationMessage($note);

            $success = match ($channelType) {
                'telegram' => $this->sendTelegramConfirmation($identifier, $confirmationMessage, $logContext),
                'whatsapp' => $this->sendWhatsAppConfirmation($identifier, $confirmationMessage, $logContext),
                default => false
            };

            if ($success) {
                Log::channel('trace')->info('NotificationService: Confirmation sent successfully', $logContext);
            } else {
                Log::channel('trace')->warning('NotificationService: Failed to send confirmation', $logContext);
            }

            return $success;

        } catch (\Exception $e) {
            Log::channel('trace')->error('NotificationService: Exception sending confirmation', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Build confirmation message based on note type and title
     *
     * @param Note $note
     * @return string
     */
    private function buildConfirmationMessage(Note $note): string
    {
        $messages = [
            Note::TYPE_SIMPLE => [
                "✅ Perfect! Am notat: *{$note->title}*",
                "✅ Salvat! *{$note->title}*",
                "✅ Am reținut: *{$note->title}*"
            ],
            Note::TYPE_TASK => [
                "✅ Task adăugat: *{$note->title}*",
                "✅ Am pus pe listă: *{$note->title}*",
                "✅ Task salvat: *{$note->title}*"
            ],
            Note::TYPE_IDEA => [
                "💡 Idee super! Am salvat: *{$note->title}*",
                "💡 Idee notată: *{$note->title}*",
                "💡 Perfect! Ideea ta: *{$note->title}*"
            ],
            Note::TYPE_SHOPING_LIST => [
                "🛒 Lista ta de cumpărături e gata!",
                "🛒 Am salvat lista de cumpărături!",
                "🛒 Lista pentru shopping e pregătită!"
            ],
            Note::TYPE_REMINDER => [
                "⏰ Memento setat: *{$note->title}*",
                "⏰ Te voi aminti de: *{$note->title}*",
                "⏰ Reminder salvat: *{$note->title}*"
            ],
            Note::TYPE_RECIPE => [
                "👩‍🍳 Rețeta salvată: *{$note->title}*",
                "👩‍🍳 Am adăugat rețeta: *{$note->title}*",
                "👩‍🍳 Rețeta ta e gata: *{$note->title}*"
            ],
            Note::TYPE_BOOKMARK => [
                "🔖 Link salvat: *{$note->title}*",
                "🔖 Bookmark adăugat: *{$note->title}*",
                "🔖 Am salvat link-ul: *{$note->title}*"
            ],
            Note::TYPE_MEASUREMENT => [
                "📏 Măsurătoare notată: *{$note->title}*",
                "📏 Am salvat măsurătoarea: *{$note->title}*",
                "📏 Date salvate: *{$note->title}*"
            ],
            Note::TYPE_EVENT => [
                "📅 Eveniment adăugat: *{$note->title}*",
                "📅 Am notat evenimentul: *{$note->title}*",
                "📅 Perfect! Eveniment salvat: *{$note->title}*"
            ],
            Note::TYPE_CONTACT => [
                "👤 Contact salvat: *{$note->title}*",
                "👤 Am adăugat contactul: *{$note->title}*",
                "👤 Contact nou: *{$note->title}*"
            ]
        ];

        // Pentru shopping list, folosesc un mesaj special fără titlu (e prea lung)
        if ($note->note_type === Note::TYPE_SHOPING_LIST) {
            $shopMessages = $messages[Note::TYPE_SHOPING_LIST];
            return $shopMessages[array_rand($shopMessages)];
        }

        // Pentru alte tipuri, aleg random din variante
        $noteMessages = $messages[$note->note_type] ?? $messages[Note::TYPE_SIMPLE];
        return $noteMessages[array_rand($noteMessages)];
    }

    /**
     * Send confirmation via Telegram
     *
     * @param string $chatId
     * @param string $message
     * @param array $logContext
     * @return bool
     */
    private function sendTelegramConfirmation(string $chatId, string $message, array $logContext): bool
    {
        Log::channel('trace')->info('NotificationService: Sending Telegram confirmation', $logContext);

        return $this->telegramService->sendMessage($chatId, $message);
    }

    /**
     * Send confirmation via WhatsApp
     *
     * @param string $phoneNumber
     * @param string $message
     * @param array $logContext
     * @return bool
     */
    private function sendWhatsAppConfirmation(string $phoneNumber, string $message, array $logContext): bool
    {
        Log::channel('trace')->info('NotificationService: Sending WhatsApp confirmation', $logContext);

        // For WhatsApp, we'll send a simple text message using the reminder service
        // We can adapt this later to use a specific template if needed
        return $this->whatsAppService->sendSimpleTextMessage($phoneNumber, $message);
    }
}
