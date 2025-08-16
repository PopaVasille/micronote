<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\IncomingMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappController extends Controller
{
    /**
     * Handles webhook requests from Meta, covering both verification (GET)
     * and incoming message notifications (POST).
     */
    public function handleWebhook(Request $request)
    {
        Log::info('intru in controller whatsup');
        // --- Handle Webhook Verification (GET request) ---
        if ($request->isMethod('get')) {
            $verifyToken = env('WHATSAPP_VERIFY_TOKEN');
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode && $token && $mode === 'subscribe' && $token === $verifyToken) {
                Log::info('WhatsApp Webhook Verified!');
                return response($challenge, 200);
            } else {
                Log::warning('WhatsApp Webhook Verification Failed: Tokens do not match.');
                return response(null, 403);
            }
        }

        // --- Handle Incoming Message Notifications (POST request) ---
        if ($request->isMethod('post')) {
            Log::info('WhatsApp Webhook Received:', $request->all());

            // Forward the request to the generic IncomingMessageController for processing
            $incomingMessageController = app(IncomingMessageController::class);
            $incomingMessageController->handleWhatsappWebhook($request);

            // It is CRITICAL to respond quickly with 200 OK.
            // The actual processing is handled by the service.
            return response()->json(['status' => 'success'], 200);
        }

        // If it's neither GET nor POST, it's a bad request.
        return response(null, 400);
    }
}
