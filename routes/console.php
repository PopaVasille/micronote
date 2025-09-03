<?php

use App\Jobs\Reminders\ProcessAndSendReminders;
use App\Jobs\SendDailySummariesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ProcessAndSendReminders)->everyMinute();

// Schedule daily summaries to be sent every morning at 8:00 AM in each user's timezone
// This runs multiple times throughout the day to catch different timezones
Schedule::job(new SendDailySummariesJob)
    ->hourlyAt(0) // Run at the top of every hour
    ->between('6:00', '12:00') // Only between 6 AM and 12 PM (covers most user timezones)
    ->withoutOverlapping(60); // Prevent overlapping executions with 60-minute grace period
