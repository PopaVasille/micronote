<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TelegramAccountController extends Controller
{
    public function showConnectForm()
    {
        return Inertia::render('Telegram/Connect', [
            'currentTelegramId' => Auth::user()->telegram_id,
        ]);
    }

    public function connect(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string|max:100|unique:users,telegram_id,'.Auth::id(),
        ]);

        $user = Auth::user();
        $user->telegram_id = $validated['telegram_id'];
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Contul Telegram a fost conectat cu succes!');
    }
}
