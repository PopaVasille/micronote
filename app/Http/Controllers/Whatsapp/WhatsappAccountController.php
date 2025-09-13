<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Jobs\SendWelcomeMessageJob;
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
            'whatsapp_id' => ['required', 'string', 'regex:/^[0-9]+$/', 'unique:users,whatsapp_id,' . Auth::id()],
        ]);

        $user = Auth::user();
        $user->whatsapp_id = $request->input('whatsapp_id');
        $user->save();

        SendWelcomeMessageJob::dispatch($user);

        return redirect()->route('dashboard')->with('success', 'Contul WhatsApp a fost conectat cu succes!');
    }

}
