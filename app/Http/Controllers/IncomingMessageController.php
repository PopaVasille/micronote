<?php

namespace App\Http\Controllers;

use App\Models\IncomingMessage;
use App\Services\Telegram\IncomingMessage\IncomingTelegramMessageProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        Log::info('Telegram webhook received by Controller.');

        // Validare preliminară (opțional, Telegram e destul de sigur)
         if (!$request->isJson()) {
             Log::warning('Webhook request is not JSON.');
             return response()->json(['status' => 'error', 'message' => 'Invalid request format'], 415);
         }

        $data = $request->all();

        // Pasăm datele către Service pentru procesare
        $processedMessage = $this->incomingTelegramMessageProcessorService->processTelegramWebhook($data);

        if ($processedMessage) {
            // Dacă Service-ul a reușit să proceseze (salva) mesajul
            Log::info('Webhook processed successfully by Controller.');
            return response()->json(['status' => 'ok']);
        } else {
            // Dacă Service-ul a întâmpinat o eroare (care e deja logată în Service)
            Log::error('Webhook processing failed in Controller.');
            // Putem returna un status diferit sau doar OK, Telegram așteaptă OK de obicei
            return response()->json(['status' => 'error', 'message' => 'Failed to process message'], 500); // Returnăm 500 pentru a indica problema
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
