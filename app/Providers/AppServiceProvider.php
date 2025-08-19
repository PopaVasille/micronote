<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Reminders\Contracts\ReminderDeliveryInterface::class,
            \App\Services\Reminders\WhatsAppReminderDelivery::class
        );

        $this->app->singleton(\App\Services\Reminders\ReminderDeliveryService::class, function ($app) {
            return new \App\Services\Reminders\ReminderDeliveryService(
                $app->make(\App\Services\Reminders\WhatsAppReminderDelivery::class),
                $app->make(\App\Services\Reminders\TelegramReminderDelivery::class)
            );
        });

        $this->app->singleton(\App\Services\WhatsApp\WhatsAppReminderService::class);
        $this->app->singleton(\App\Services\Telegram\TelegramReminderService::class);
        $this->app->singleton(\App\Services\Reminders\ReminderSchedulingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
