<?php

namespace App\Services\Reminders;

use App\Models\User;
use App\Services\Reminders\Contracts\ReminderDeliveryInterface;
use App\Services\WhatsApp\WhatsAppReminderService;

class WhatsAppReminderDelivery implements ReminderDeliveryInterface
{
    public function __construct(
        private WhatsAppReminderService $whatsAppService
    ) {}

    public function sendReminder(string $recipient, string $reminderTitle, string $reminderContent): bool
    {
        return $this->whatsAppService->sendReminderTemplate($recipient, $reminderTitle, $reminderContent);
    }

    public function isAvailable(object $user): bool
    {
        return $user instanceof User && !empty($user->whatsapp_phone);
    }

    public function getDeliveryType(): string
    {
        return 'whatsapp';
    }
}