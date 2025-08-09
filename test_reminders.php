<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Reminder;
use Carbon\Carbon;

echo "=== Debugging Reminders ===\n";
echo "Current time: " . now()->toDateTimeString() . "\n\n";

// Check all reminders
$allReminders = Reminder::with('note.user')->get();
echo "Total reminders in DB: " . $allReminders->count() . "\n";

foreach ($allReminders as $reminder) {
    echo "Reminder #{$reminder->id}:\n";
    echo "  - next_remind_at: {$reminder->next_remind_at}\n";
    echo "  - Is overdue? " . ($reminder->next_remind_at <= now() ? 'YES' : 'NO') . "\n";
    echo "  - Note completed? " . ($reminder->note->is_completed ? 'YES' : 'NO') . "\n";
    echo "  - User: " . ($reminder->note->user->name ?? 'N/A') . "\n";
    echo "  - Telegram ID: " . ($reminder->note->user->telegram_id ?? 'N/A') . "\n";
    echo "  - Message: " . ($reminder->message ?? $reminder->note->content ?? 'N/A') . "\n\n";
}

// Check overdue reminders (same query as job)
$overdueReminders = Reminder::where('next_remind_at', '<=', now())
    ->whereHas('note', function ($query) {
        $query->where('is_completed', false);
    })
    ->with('note.user')
    ->get();

echo "Overdue reminders: " . $overdueReminders->count() . "\n";

// Test the job
echo "\n=== Testing Job ===\n";
$job = new App\Jobs\Reminders\ProcessAndSendReminders();
$job->handle();

echo "Job executed.\n";