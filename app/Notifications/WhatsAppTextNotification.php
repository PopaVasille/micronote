<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WhatsApp\WhatsAppChannel;
use NotificationChannels\WhatsApp\WhatsAppTextMessage;

class WhatsAppTextNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $message
    ) {}

    public function via(object $notifiable): array
    {
        return [WhatsAppChannel::class];
    }

    public function toWhatsApp(object $notifiable): WhatsAppTextMessage
    {
        return WhatsAppTextMessage::create()
            ->message($this->message);
    }
}