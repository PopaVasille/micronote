<?php

use App\Http\Controllers\IncomingMessageController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Telegram\TelegramBotController;
use App\Http\Controllers\Whatsapp\WhatsappController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ruta pentru webhook-ul Telegram
Route::post('/telegram/webhook/bot', [TelegramBotController::class, 'handleWebhook'])->name('telegram.webhook');
// Ruta pentru webhook-ul WhatsApp (acceptă GET pentru verificare și POST pentru mesaje)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsappController::class, 'handleWebhook'])->name('whatsapp.webhook');
//Route::get('/telegram/setwebhook', [TelegramBotController::class, 'setWebhook']);

Route::middleware('auth:sanctum')->group(function () {
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
