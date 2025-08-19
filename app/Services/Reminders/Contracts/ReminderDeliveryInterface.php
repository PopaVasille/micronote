<?php

namespace App\Services\Reminders\Contracts;

interface ReminderDeliveryInterface
{
    public function sendReminder(string $recipient, string $reminderTitle, string $reminderContent): bool;
    
    public function isAvailable(object $user): bool;
    
    public function getDeliveryType(): string;
}