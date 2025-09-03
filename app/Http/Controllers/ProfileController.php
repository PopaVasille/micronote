<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Jobs\SendDailySummariesJob;
use App\Services\DailySummary\DailySummaryService;
use App\Services\Messaging\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $request->user()->only([
                'id', 'name', 'email', 'telegram_id', 'whatsapp_id', 'whatsapp_phone',
                'daily_summary_enabled', 'daily_summary_time', 'daily_summary_timezone'
            ]),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update daily summary settings
     */
    public function updateDailySummary(Request $request): RedirectResponse
    {
        $request->validate([
            'daily_summary_enabled' => 'required|boolean',
            'daily_summary_time' => 'required|string',
            'daily_summary_timezone' => 'required|string|in:Europe/Bucharest,Europe/London,Europe/Paris,Europe/Berlin,Europe/Madrid,Europe/Rome,America/New_York,America/Los_Angeles,America/Chicago,Asia/Tokyo,Asia/Shanghai,Australia/Sydney',
        ]);

        // Custom validation for time format
        $timePattern = '/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/';
        if (!preg_match($timePattern, $request->daily_summary_time)) {
            return back()->withErrors([
                'daily_summary_time' => 'Formatul orei trebuie să fie HH:MM (de ex. 08:00).'
            ]);
        }

        $validated = [
            'daily_summary_enabled' => $request->boolean('daily_summary_enabled'),
            'daily_summary_time' => $request->daily_summary_time,
            'daily_summary_timezone' => $request->daily_summary_timezone,
        ];

        $user = $request->user();

        // Check if user has a messaging platform connected when enabling
        if ($validated['daily_summary_enabled'] &&
            !($user->telegram_id || $user->whatsapp_id || $user->whatsapp_phone)) {
            return back()->withErrors([
                'daily_summary_enabled' => 'Trebuie să conectezi o platformă de mesagerie (Telegram sau WhatsApp) pentru a activa rezumatul zilnic.'
            ]);
        }

        $user->update($validated);

        Log::channel('trace')->info('User updated daily summary settings', [
            'user_id' => $user->id,
            'settings' => $validated
        ]);

        return back()->with('status', 'Setările de rezumat zilnic au fost salvate cu succes.');
    }

    /**
     * Send test daily summary
     */
    public function testDailySummary(
        Request $request,
        DailySummaryService $summaryService,
        NotificationService $notificationService
    ): RedirectResponse {
        $user = $request->user();

        // Check if daily summary is enabled and user has messaging platform
        if (!$user->daily_summary_enabled) {
            return back()->withErrors([
                'test' => 'Rezumatul zilnic trebuie să fie activat pentru a trimite un test.'
            ]);
        }

        if (!($user->telegram_id || $user->whatsapp_id || $user->whatsapp_phone)) {
            return back()->withErrors([
                'test' => 'Trebuie să conectezi o platformă de mesagerie pentru a trimite un test.'
            ]);
        }

        $correlationId = 'daily-summary-test-' . $user->id . '-' . now()->timestamp;

        try {
            // Generate summary
            $summaryContent = $summaryService->generateDailySummary($user);

            if (!$summaryContent) {
                // If no content, send a demo message
                $summaryContent = $this->generateDemoSummary($user);
            }

            // Send via preferred channel
            $success = false;
            if ($user->telegram_id) {
                $success = $notificationService->sendCustomMessage(
                    'telegram',
                    $user->telegram_id,
                    $summaryContent,
                    $correlationId
                );
            } elseif ($user->whatsapp_id || $user->whatsapp_phone) {
                $phoneNumber = $user->whatsapp_phone ?? $user->whatsapp_id;
                $success = $notificationService->sendCustomMessage(
                    'whatsapp',
                    $phoneNumber,
                    $summaryContent,
                    $correlationId
                );
            }

            if ($success) {
                return back()->with('status', 'Exemplul de rezumat zilnic a fost trimis cu succes!');
            } else {
                return back()->withErrors([
                    'test' => 'A apărut o eroare la trimiterea testului. Te rog încearcă din nou.'
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('trace')->error('Failed to send test daily summary', [
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'test' => 'A apărut o eroare tehnică. Te rog încearcă din nou mai târziu.'
            ]);
        }
    }

    /**
     * Generate a demo summary when user has no relevant notes
     */
    private function generateDemoSummary($user): string
    {
        $today = Carbon::now($user->daily_summary_timezone ?? 'Europe/Bucharest');
        $hour = $today->hour;
        $dayName = $today->locale('ro')->dayName;
        $formattedDate = $today->format('d.m.Y');

        $timeGreeting = match (true) {
            $hour < 12 => 'Bună dimineața',
            $hour < 17 => 'Bună ziua',
            default => 'Bună seara'
        };

        $greeting = "🌅 {$timeGreeting}, {$user->name}!\n📅 Rezumatul pentru {$dayName}, {$formattedDate}";

        $demoContent = "\n\n✅ **Task-uri pentru azi** (2)\n" .
            "🔥 Finalizează prezentarea (14:00)\n" .
            "▫️ Verifică email-urile\n\n" .
            "📅 **Evenimente** (1)\n" .
            "🔸 Ședință echipă (10:30)\n\n" .
            "⏰ **Memento-uri** (1)\n" .
            "🔔 Sună doctorul (16:00)";

        $footer = "\n\n📱 _Pentru a gestiona notițele, accesează dashboard-ul web sau trimite-mi un mesaj!_\n\n" .
            "🧪 *Acesta este un exemplu de rezumat zilnic. Conținutul real va include notițele tale actuale.*";

        return $greeting . $demoContent . $footer;
    }
}
