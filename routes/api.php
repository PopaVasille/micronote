<?php

use App\Http\Controllers\IncomingMessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ruta pentru webhook-ul Telegram
Route::post('/telegram/webhook/micronote12341234', [IncomingMessageController::class, 'handleTelegramWebhook']);
