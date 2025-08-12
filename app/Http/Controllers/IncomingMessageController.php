<?php

namespace App\Http\Controllers;

use App\Models\IncomingMessage;
use App\Services\Telegram\IncomingMessage\IncomingTelegramMessageProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IncomingMessageController extends Controller
{
    // Injectăm Service-ul prin constructor
    public function __construct(readonly IncomingTelegramMessageProcessorService $incomingTelegramMessageProcessorService)
    {

    }

    /**
     * Handle the incoming Telegram webhook.
     */
    public function handleTelegramWebhook(Request $request)
    {
        $correlationId = Str::uuid()->toString();
        $logContext = ['correlation_id' => $correlationId];

        Log::channel('trace')->info('Telegram webhook received.', array_merge($logContext, ['request_data' => $request->all()]));

         if (!$request->isJson()) {
             Log::channel('trace')->warning('Webhook request is not JSON.', $logContext);
             return response()->json(['status' => 'error', 'message' => 'Invalid request format'], 415);
         }

        $data = $request->all();

        // Pasăm datele și ID-ul de corelare către Service pentru procesare
        $processedMessage = $this->incomingTelegramMessageProcessorService->processTelegramWebhook($data, $correlationId);

        if ($processedMessage) {
            Log::channel('trace')->info('Webhook processed successfully.', $logContext);
            return response()->json(['status' => 'ok']);
        } else {
            Log::channel('trace')->error('Webhook processing failed.', $logContext);
            return response()->json(['status' => 'error', 'message' => 'Failed to process message'], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(IncomingMessage $incomingMessage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IncomingMessage $incomingMessage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncomingMessage $incomingMessage)
    {
        //
    }
}
