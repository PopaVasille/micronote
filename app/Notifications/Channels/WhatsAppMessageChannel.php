<?php

namespace App\Notifications\Channels;

use App\Services\WhatsApp\WhatsAppReminderService;
use Illuminate\Notifications\Notification;

class WhatsAppMessageChannel
{
    public function __construct(private readonly WhatsAppReminderService $whatsAppService) {}

    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toWhatsApp($notifiable);
        $whatsappId = $notifiable->routeNotificationFor('whatsapp', $notification);

        if ($whatsappId) {
            $this->whatsAppService->sendSimpleTextMessage($whatsappId, $message);
        }
    }
}
