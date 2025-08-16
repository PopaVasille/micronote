<?php

namespace App\Services\Whatsapp\IncomingMessage;

use App\Models\User;
use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use Illuminate\Support\Facades\Log;

class IncomingWhatsappMessageProcessorService
{
    protected IncomingMessageRepositoryInterface $incomingMessageRepository;

    public function __construct(IncomingMessageRepositoryInterface $incomingMessageRepository)
    {
        $this->incomingMessageRepository = $incomingMessageRepository;
    }

    public function processWhatsappWebhook(array $data, string $correlationId): ?\App\Models\IncomingMessage
    {
        $logContext = ['correlation_id' => $correlationId, 'data' => $data];
        Log::channel('trace')->info('Processing Meta WhatsApp webhook...', $logContext);

        // --- Start Meta Payload Parsing ---
        $value = $data['entry'][0]['changes'][0]['value'] ?? null;
        $message = $value['messages'][0] ?? null;
        $contact = $value['contacts'][0] ?? null;

        if (!$message || !$contact) {
            Log::channel('trace')->info('Webhook received, but it is not a message type (e.g., a status update). Skipping.', $logContext);
            return null;
        }

        $wa_id = $contact['wa_id'] ?? null;
        $messageContent = $message['text']['body'] ?? null;
        $messageType = $message['type'] ?? null;

        if ($messageType !== 'text' || !$messageContent) {
            Log::channel('trace')->info('Webhook is not a text message or has no content. Skipping.', $logContext);
            return null;
        }
        // --- End Meta Payload Parsing ---

        // --- Handle /start command for account linking ---
        if (trim($messageContent) === '/start') {
            $replyMessage = "Your WhatsApp ID is: {$wa_id}. Please copy and paste this into the application to link your account.";
            Log::channel('info')->info(
                "[/start command] - User with wa_id [{$wa_id}] initiated linking. Reply to send: \"{$replyMessage}\"",
                $logContext
            );
            // In the future, an API call to Meta to send the reply would go here.
            return null; // Stop processing, as this is not a note.
        }

        // --- Process regular messages ---
        if (!$wa_id) {
            Log::channel('trace')->warning('Meta WhatsApp webhook missing wa_id.', $logContext);
            return null;
        }

        // Find the user by their WhatsApp ID
        $user = User::where('whatsapp_id', $wa_id)->first();

        $messageData = [
            'user_id' => $user ? $user->id : null,
            'source_type' => 'whatsapp',
            'sender_identifier' => $wa_id, // Use wa_id as the unique identifier
            'message_content' => $messageContent,
            'is_processed' => false,
            'metadata' => $data, // Store the full payload in metadata
        ];

        try {
            $createdMessage = $this->incomingMessageRepository->create($messageData);
            Log::channel('trace')->info('Successfully saved incoming WhatsApp message.', ['correlation_id' => $correlationId, 'message_id' => $createdMessage->id]);
            return $createdMessage;
        } catch (\Exception $e) {
            Log::channel('trace')->error('Failed to save incoming WhatsApp message.', ['correlation_id' => $correlationId, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
