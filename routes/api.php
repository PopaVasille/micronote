<?php

use App\Http\Controllers\NoteController;
use App\Http\Controllers\Telegram\TelegramController;
use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/telegram/webhook/bot', [TelegramController::class, 'handleWebhook'])
    ->middleware(['verify.telegram.webhook', 'throttle:webhook-telegram'])
    ->name('telegram.webhook');

Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsappController::class, 'webhook'])
    ->middleware(['throttle:webhook-whatsapp'])
    ->name('whatsapp.webhook');


Route::middleware(['auth:sanctum', 'throttle:api-users'])->group(function () {
    // Listarea notițelor utilizatorului
    Route::get('/notes', [NoteController::class, 'dashboard'])
        ->name('api.notes.dashboard');

    // Crearea unei noi notițe
    Route::post('/notes', [NoteController::class, 'store'])
        ->name('api.notes.store');

    // Afișarea unei singure notițe
    Route::get('/notes/{note}', [NoteController::class, 'show'])
        ->name('api.notes.show');

    // Actualizarea parțială a unei notițe (PATCH este recomandat pentru actualizări parțiale)
    Route::post('/notes/update/{note}', [NoteController::class, 'update'])
        ->name('api.notes.update');

    // Ștergerea unei notițe
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])
        ->name('api.notes.destroy');

    // Opțional - rută pentru statistici de note
    Route::get('/notes/stats', [NoteController::class, 'stats'])
        ->name('api.notes.stats');
});
