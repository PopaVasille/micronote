<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Ruta pentru webhook-ul Telegram
//Route::post('/telegram/webhook/micronote12341234', function (Request $request) {
//    // Pentru moment, doar loghează request-ul să vezi dacă ajunge aici
//    Log::info('intru pe aici');
//    Log::info('Telegram Webhook Received:',$request->all());
//    // Răspunde rapid pentru a nu bloca Telegram
//    return response()->json(['status' => 'ok']);
//});

require __DIR__.'/auth.php';
