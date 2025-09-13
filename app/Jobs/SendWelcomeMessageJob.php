<?php

namespace App\Jobs;

use App;
use App\Models\User;
use App\Notifications\AccountLinkedWelcomeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWelcomeMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $locale = $this->user->language ?? config('app.fallback_locale');

        $this->user->notify((new AccountLinkedWelcomeNotification())->locale($locale));
    }
}
