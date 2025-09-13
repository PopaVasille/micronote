<?php

namespace App\Notifications\Channels;

use App\Services\Telegram\TelegramService;
use Illuminate\Notifications\Notification;

class TelegramMessageChannel
{
    public function __construct(private readonly TelegramService $telegramService)
    {
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        // Get the message content from the notification class
        $message = $notification->toTelegram($notifiable);

        // Get the user's Telegram ID
        $telegramId = $notifiable->routeNotificationFor('telegram', $notification);

        if (!$telegramId) {
            return;
        }

        // Send the message via the TelegramService
        $this->telegramService->sendMessage($telegramId, $message);
    }
}
