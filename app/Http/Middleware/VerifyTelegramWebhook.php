<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simple Telegram webhook verification middleware
 * Only verifies secret token if configured, otherwise passes through
 */
class VerifyTelegramWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to Telegram webhook routes
        if (!$request->is('api/telegram/webhook/*')) {
            return $next($request);
        }

        // Get the expected secret token from config
        $expectedToken = config('services.telegram-bot-api.webhook_secret');
        $providedToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        // If no secret token is configured, just pass through (for backward compatibility)
        if (empty($expectedToken)) {
            Log::channel('telegram')->info('Telegram webhook: No secret token configured - allowing request', [
                'ip' => $request->ip(),
            ]);
            return $next($request);
        }

        // If secret token is configured but not provided, reject
        if (empty($providedToken)) {
            Log::warning('Telegram webhook: Request rejected - secret token configured but not provided', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify token
        if (!hash_equals($expectedToken, $providedToken)) {
            Log::warning('Telegram webhook: Request rejected - invalid secret token', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'provided_length' => strlen($providedToken),
                'expected_length' => strlen($expectedToken),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Token is valid, allow request
        Log::channel('telegram')->info('Telegram webhook: Request authorized successfully', [
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
