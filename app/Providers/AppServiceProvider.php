<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for API and webhook endpoints
     */
    protected function configureRateLimiting(): void
    {
        // API Rate Limiting - 20 requests per minute for authenticated users
        RateLimiter::for('api-users', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    \Illuminate\Support\Facades\Log::warning('API rate limit exceeded', [
                        'ip' => request()->ip(),
                        'user_id' => auth()->id(),
                        'time' => now()
                    ]);
                });
        });

        // Telegram Webhook Rate Limiting - 30 requests per minute (AI processing limit)
        RateLimiter::for('webhook-telegram', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(function () {
                    \Illuminate\Support\Facades\Log::warning('Telegram webhook rate limit exceeded', [
                        'ip' => request()->ip(),
                        'time' => now()
                    ]);
                });
        });

        // WhatsApp Webhook Rate Limiting - 30 requests per minute (AI processing limit)
        RateLimiter::for('webhook-whatsapp', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip())
                ->response(function () {
                    \Illuminate\Support\Facades\Log::warning('WhatsApp webhook rate limit exceeded', [
                        'ip' => request()->ip(),
                        'time' => now()
                    ]);
                });
        });
    }
}
