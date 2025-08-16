<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WhatsappAccountController extends Controller
{
    /**
     * Show the form for connecting a WhatsApp account.
     */
    public function showConnectForm()
    {
        // This assumes you have an Inertia page component at:
        // resources/js/Pages/Whatsapp/Connect.vue
        return Inertia::render('Whatsapp/Connect', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Handle the submission of the connect form.
     */
    public function connect(Request $request)
    {
        $request->validate([
            'wa_id' => ['required', 'string', 'regex:/^[0-9]+$/', 'unique:users,whatsapp_id'],
        ]);

        $user = Auth::user();
        $user->whatsapp_id = $request->input('wa_id');
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Your WhatsApp account has been successfully connected.');
    }
}
