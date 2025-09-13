<?php

namespace App\Services\Reminders;

use App\Models\User;
use App\Services\Reminders\Contracts\ReminderDeliveryInterface;
use App\Services\Telegram\TelegramService;

class TelegramReminderDelivery implements ReminderDeliveryInterface
{
    public function __construct(
        private TelegramService $telegramService
    ) {}

    public function sendReminder(string $recipient, string $reminderTitle, string $reminderContent): bool
    {
        return $this->telegramService->sendReminder($recipient, $reminderContent);
    }

    public function isAvailable(object $user): bool
    {
        return $user instanceof User && !empty($user->telegram_id);
    }

    public function getDeliveryType(): string
    {
        return 'telegram';
    }
}