<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramMessageChannel;
use App\Notifications\Channels\WhatsAppMessageChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AccountLinkedWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        $channels = [];

        // Only send to the platform that was just connected.
        // We can pass the platform name in the constructor.
        // For now, let's assume we want to notify on all available channels.

        if ($notifiable->telegram_id) {
            $channels[] = TelegramMessageChannel::class;
        }
        if ($notifiable->whatsapp_id) {
            $channels[] = WhatsAppMessageChannel::class;
        }

        return $channels;
    }

    /**
     * Get the Telegram representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toTelegram($notifiable): string
    {
        return __('bot.welcome_message');
    }

    /**
     * Get the WhatsApp representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toWhatsApp($notifiable): string
    {
        // Strip markdown for WhatsApp, which uses a different syntax
        $message = __('bot.welcome_message');
        $message = str_replace('**', '*', $message);
        $message = str_replace('__', '_', $message);

        return $message;
    }
}
