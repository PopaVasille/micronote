<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Telegram\TelegramAccountController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

// Landing Page
Route::get('/', [LandingPageController::class, 'show'])->name('landing');
Route::post('/early-access', [LandingPageController::class, 'store'])->name('early-access.store');


Route::middleware(['auth', 'verified'])->group(function () {
    //telegram Connect
    Route::get('/telegram/connect', [TelegramAccountController::class, 'showConnectForm'])
        ->name('telegram.connect');
    Route::post('/telegram/connect', [TelegramAccountController::class, 'connect'])
        ->name('telegram.store');
    //dashboard
    Route::get('/dashboard', [NoteController::class, 'dashboard'])->name('dashboard');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    //notes
    Route::post('/notes/{note}/toggle-favorite', [NoteController::class, 'toggleFavorite'])
        ->name('notes.toggle-favorite');
    Route::post('/notes/{note}/toggle-completed', [NoteController::class, 'toggleCompleted'])
        ->name('notes.toggle-completed');
    Route::post('/notes/{note}/update', [NoteController::class, 'update'])
        ->name('notes.update');
    Route::put('/notes/{note}/shopping-list', [NoteController::class, 'updateShoppingList'])
        ->name('notes.shopping.update');
    // Adaugă și celelalte rute
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';