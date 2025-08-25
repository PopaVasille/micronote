<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Services\Commands\CommandProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WhatsAppController handles Meta WhatsApp webhook requests
 *
 * This controller is organized under WhatsApp/ namespace following the
 * channel-based organization pattern. It handles both webhook verification
 * and incoming message processing using the unified architecture.
 */
class WhatsAppController extends Controller
{
    public function __construct(
        private readonly CommandProcessor $commandProcessor
    ) {}

    /**
     * Handle WhatsApp webhook requests
     * Supports both GET (verification) and POST (message processing)
     *
     * @param Request $request
     * @return Response
     */
    public function webhook(Request $request): Response
    {
        Log::channel('trace')->info('WhatsAppController: Webhook request received', [
            'method' => $request->method(),
            'url' => $request->url()
        ]);

        if ($request->isMethod('get')) {
            return $this->handleWebhookVerification($request);
        }

        if ($request->isMethod('post')) {
            return $this->handleIncomingMessage($request);
        }

        Log::warning('WhatsAppController: Invalid HTTP method', [
            'method' => $request->method()
        ]);

        return response('Method not allowed', 405);
    }

    /**
     * Handle webhook verification (GET request)
     *
     * Meta sends GET requests to verify the webhook endpoint
     * with hub_mode=subscribe, hub_verify_token, and hub_challenge parameters
     *
     * @param Request $request
     * @return Response
     */
    private function handleWebhookVerification(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::channel('trace')->info('WhatsAppController: Webhook verification request', [
            'hub_mode' => $mode,
            'token_provided' => !empty($token),
            'challenge_provided' => !empty($challenge)
        ]);

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsAppController: Webhook verification successful');
            return response($challenge, 200);
        }

        Log::warning('WhatsAppController: Webhook verification failed', [
            'expected_token' => !empty($verifyToken),
            'provided_token' => $token,
            'mode' => $mode
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages (POST request)
     *
     * Uses the unified architecture: commands go to CommandProcessor,
     * regular messages are dispatched to background processing
     *
     * @param Request $request
     * @return Response
     */
    private function handleIncomingMessage(Request $request): Response
    {
        $correlationId = Str::uuid();

        Log::channel('trace')->info('WhatsAppController: Processing incoming message', [
            'correlation_id' => $correlationId,
        ]);

        try {
            // Parse WhatsApp webhook payload
            $entry = $request->input('entry', []);

            foreach ($entry as $entryItem) {
                $changes = $entryItem['changes'] ?? [];

                foreach ($changes as $change) {
                    if ($change['field'] !== 'messages') {
                        continue; // Skip non-message changes
                    }

                    $value = $change['value'] ?? [];
                    $messages = $value['messages'] ?? [];

                    foreach ($messages as $message) {
                        $this->processMessage($message, $value, $correlationId);
                    }
                }
            }

            Log::channel('trace')->info('WhatsAppController: Webhook processing completed', [
                'correlation_id' => $correlationId
            ]);

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('WhatsAppController: Webhook processing failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Always return 200 to prevent Meta from retrying
            return response('OK', 200);
        }
    }

    /**
     * Process individual WhatsApp message
     *
     * Extracts message data and routes to appropriate handler:
     * - Commands (like /start) go to CommandProcessor
     * - Regular messages are dispatched to background job
     *
     * @param array $message
     * @param array $value
     * @param string $correlationId
     * @return void
     */
    private function processMessage(array $message, array $value, string $correlationId): void
    {
        $logContext = ['correlation_id' => $correlationId];

        // Extract message data
        $phoneNumber = $message['from'] ?? null;
        $messageText = $message['text']['body'] ?? '';
        $messageId = $message['id'] ?? null;
        $messageType = $message['type'] ?? null;

        // Extract wa_id from contacts
        $contacts = $value['contacts'] ?? [];
        $contact = $contacts[0] ?? null;
        $waId = $contact['wa_id'] ?? null;

        $logContext = [
            ...$logContext,
            'phone_number' => $phoneNumber,
            'wa_id' => $waId,
            'message_id' => $messageId,
            'message_type' => $messageType
        ];

        // Validate required data
        if (!$phoneNumber || !$messageId || $messageType !== 'text' || !$messageText) {
            Log::channel('trace')->info('WhatsAppController: Skipping non-text message or missing data', $logContext);
            return;
        }

        if (!$waId) {
            Log::channel('trace')->warning('WhatsAppController: Missing wa_id in message', $logContext);
            return;
        }

        Log::channel('trace')->info('WhatsAppController: Processing WhatsApp message', [
            ...$logContext,
            'message_preview' => Str::limit($messageText, 50)
        ]);

        // Prepare metadata for command/job processing
        $metadata = [
            'phone_number' => $phoneNumber,
            'wa_id' => $waId,
            'message_id' => $messageId,
            'raw_data' => $value
        ];

        // Check if message is a command
        if ($this->commandProcessor->isCommand($messageText)) {
            Log::channel('trace')->info('WhatsAppController: Message identified as command', $logContext);

            try {
                $commandExecuted = $this->commandProcessor->process(
                    $messageText,
                    'whatsapp',
                    $waId,
                    $metadata
                );

                Log::channel('trace')->info('WhatsAppController: Command processing completed', [
                    ...$logContext,
                    'command_executed' => $commandExecuted
                ]);

            } catch (\Exception $e) {
                Log::error('WhatsAppController: Command processing failed', [
                    ...$logContext,
                    'error' => $e->getMessage()
                ]);
            }
            return;
        }

        // Regular message - dispatch to background processing
        Log::channel('trace')->info('WhatsAppController: Dispatching message to background processing', $logContext);

        try {
            ProcessIncomingMessageJob::dispatchSync(
                'whatsapp',
                $waId,
                $messageText,
                $value, // Raw WhatsApp webhook data
                $correlationId
            );

            Log::channel('trace')->info('WhatsAppController: Message dispatched to background job', $logContext);

        } catch (\Exception $e) {
            Log::error('WhatsAppController: Failed to dispatch background job', [
                ...$logContext,
                'error' => $e->getMessage()
            ]);
        }
    }
}
