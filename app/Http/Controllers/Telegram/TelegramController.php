<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Services\Commands\CommandProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * TelegramController handles Telegram webhook requests
 *
 * This refactored controller follows the new unified architecture:
 * - Commands are handled by CommandProcessor
 * - Regular messages are processed asynchronously via ProcessIncomingMessageJob
 * - Quick response to webhooks (under 3 seconds)
 */
class TelegramController extends Controller
{
    protected Api $telegram;

    /**
     * TelegramController constructor
     *
     * @throws TelegramSDKException
     */
    public function __construct()
    {
        $this->telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
    }

    /**
     * Handle incoming Telegram webhook
     *
     * This method replicates the exact functionality of the original
     * TelegramBotController but uses the new unified architecture.
     *
     * @param Request $request
     * @param CommandProcessor $commandProcessor
     * @return JsonResponse
     * @throws TelegramSDKException
     */
    public function handleWebhook(Request $request, CommandProcessor $commandProcessor): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $logContext = ['correlation_id' => $correlationId];

        Log::channel('trace')->info('TelegramController: Webhook received', $logContext);

        try {
            // Get webhook update from Telegram SDK (same as original)
            $update = $this->telegram->getWebhookUpdate();

            Log::info('intru prin webhook: ' . json_encode($update));

            // Validate webhook structure
            if (!isset($update['message']['from']['id']) || !isset($update['message']['text'])) {
                Log::channel('trace')->warning('TelegramController: Invalid webhook structure', [
                    ...$logContext,
                    'update' => $update
                ]);
                return response()->json(['status' => 'error', 'message' => 'Invalid webhook structure'], 400);
            }

            $telegramId = (string) $update['message']['from']['id'];
            $messageContent = $update['message']['text'];
            $chatId = $update['message']['chat']['id'];

            $logContext['telegram_id'] = $telegramId;
            $logContext['chat_id'] = $chatId;

            Log::channel('trace')->info('TelegramController: Message extracted', [
                ...$logContext,
                'message_content' => $messageContent
            ]);

            // Check if message is a command
            if ($commandProcessor->isCommand($messageContent)) {
                Log::channel('trace')->info('TelegramController: Processing command', [
                    ...$logContext,
                    'command' => $messageContent
                ]);

                // Prepare metadata for command processing
                $updateArray = $update->toArray();
                $metadata = [
                    'message' => $updateArray['message'],
                    'chat_id' => $chatId,
                    'update' => $updateArray
                ];

                // Process command synchronously for immediate response
                $commandResult = $commandProcessor->process(
                    $messageContent,
                    'telegram',
                    $telegramId,
                    $metadata
                );

                if ($commandResult) {
                    Log::channel('trace')->info('TelegramController: Command processed successfully', $logContext);
                } else {
                    Log::channel('trace')->warning('TelegramController: Command processing failed', $logContext);
                }

                // Always return success for webhooks to prevent retries
                return response()->json(['status' => 'success']);
            }

            // Process regular message (sync in development, async in production)
            Log::channel('trace')->info('TelegramController: Dispatching message for processing', $logContext);

            if (config('app.process_messages_sync', false)) {
                ProcessIncomingMessageJob::dispatchSync(
                    'telegram',
                    $telegramId,
                    $messageContent,
                    $update->toArray(), // Convert Telegram Update object to array
                    $correlationId
                );
            } else {
                ProcessIncomingMessageJob::dispatch(
                    'telegram',
                    $telegramId,
                    $messageContent,
                    $update->toArray(), // Convert Telegram Update object to array
                    $correlationId
                );
            }

            Log::channel('trace')->info('TelegramController: Message dispatched successfully', $logContext);

            // Quick response to Telegram
            return response()->json(['status' => 'success']);

        } catch (TelegramSDKException $e) {
            Log::channel('trace')->error('TelegramController: Telegram SDK error', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Telegram API error'
            ], 500);

        } catch (\Exception $e) {
            Log::channel('trace')->error('TelegramController: Unexpected error', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Set webhook URL for Telegram bot
     *
     * This method remains unchanged from the original implementation
     *
     * @return JsonResponse
     * @throws TelegramSDKException
     */
    public function setWebhook(): JsonResponse
    {
        $result = $this->telegram->setWebhook([
            'url' => route('telegram.webhook')
        ]);

        return response()->json(['success' => $result]);
    }
}