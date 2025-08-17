<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\IncomingMessage;
use App\Notifications\WhatsAppLinkNotification;
use App\Services\Whatsapp\IncomingMessage\IncomingWhatsappMessageProcessorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class WhatsAppController extends Controller
{
    private ?string $currentWaId = null;

    public function __construct(
        private IncomingWhatsappMessageProcessorService $messageProcessor
    ) {}

    public function webhook(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');
        Log::info('intru in webhook de whats up');
        // Webhook verification
        if ($request->has('hub_mode') && $request->get('hub_mode') === 'subscribe') {
            if ($request->get('hub_verify_token') === $verifyToken) {
                return response($request->get('hub_challenge'), 200);
            }
            return response('Forbidden', 403);
        }

        // Process incoming messages
        $entry = $request->input('entry', []);

        foreach ($entry as $entryItem) {
            $changes = $entryItem['changes'] ?? [];

            foreach ($changes as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];

                foreach ($messages as $message) {
                    $this->processMessage($message, $value);
                }
            }
        }

        return response('OK', 200);
    }

    private function processMessage(array $message, array $value): void
    {
        $phoneNumber = $message['from'] ?? null;
        $messageText = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? null;

        // Extract wa_id from the contacts info
        $contacts = $value['contacts'] ?? [];
        $contact = $contacts[0] ?? null;
        $waId = $contact['wa_id'] ?? null;

        // Store wa_id for use in other methods
        $this->currentWaId = $waId;

        if (!$phoneNumber || !$messageId) {
            Log::warning('WhatsApp message missing phone number or ID', ['message' => $message]);
            return;
        }

        Log::info('Processing WhatsApp message', [
            'phone' => $phoneNumber,
            'wa_id' => $waId,
            'message' => $messageText,
            'id' => $messageId
        ]);

        // Handle /start command for account linking
        if (trim($messageText) === '/start') {
            $this->handleStartCommand($phoneNumber);
            return;
        }

        // Find user by WhatsApp ID (similar to telegram_id)
        $user = User::where('whatsapp_id', $waId)->first();

        if (!$user) {
            // Send linking instruction
            $this->sendLinkingInstruction($phoneNumber);
            return;
        }

        // Process message similar to Telegram
        $this->processUserMessage($user, $message, $value);
    }

    private function handleStartCommand(string $phoneNumber): void
    {
        // Get wa_id from the webhook data
        $waId = $this->getWaIdFromCurrentMessage();

        if (!$waId) {
            Log::error('Could not extract wa_id from WhatsApp message', ['phone' => $phoneNumber]);
            return;
        }

        // Send wa_id to user for manual linking in web interface
        try {
            $message = "🔗 ID-ul tău WhatsApp pentru conectare este: *{$waId}*\n\n";
            $message .= "Accesează aplicația web și introdu acest ID în pagina de conectare WhatsApp.";

            Notification::route('whatsapp', $phoneNumber)
                ->notify(new \App\Notifications\WhatsAppTextNotification($message));
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp ID notification', [
                'phone' => $phoneNumber,
                'wa_id' => $waId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getWaIdFromCurrentMessage(): ?string
    {
        // This will be set from the current processing context
        return $this->currentWaId ?? null;
    }

    private function sendLinkingInstruction(string $phoneNumber): void
    {
        try {
            $message = "⚠️ Contul WhatsApp nu este conectat la aplicație!\n\n";
            $message .= "Pentru a conecta contul:\n";
            $message .= "1. Trimite comanda: */start*\n";
            $message .= "2. Vei primi ID-ul de conectare\n";
            $message .= "3. Introdu ID-ul în aplicația web\n\n";
            $message .= "După conectare poți trimite notițe direct prin WhatsApp! 📝";

            Notification::route('whatsapp', $phoneNumber)
                ->notify(new \App\Notifications\WhatsAppTextNotification($message));
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp linking instruction', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processUserMessage(User $user, array $message, array $value): void
    {
        try {
            // Use the existing WhatsApp service to process the message
            $correlationId = Str::uuid();

            // Build the data structure expected by the service
            $webhookData = [
                'entry' => [
                    [
                        'changes' => [
                            [
                                'value' => $value
                            ]
                        ]
                    ]
                ]
            ];

            $this->messageProcessor->processWhatsappWebhook($webhookData, $correlationId);

        } catch (\Exception $e) {
            Log::error('Failed to process WhatsApp message', [
                'user_id' => $user->id,
                'message_id' => $message['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }
}
