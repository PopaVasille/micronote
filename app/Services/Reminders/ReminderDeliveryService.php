<?php

namespace App\Services\Reminders;

use App\Models\Reminder;
use App\Models\User;
use App\Services\Reminders\Contracts\ReminderDeliveryInterface;
use Illuminate\Support\Facades\Log;

class ReminderDeliveryService
{
    private array $deliveryProviders = [];

    public function __construct(
        WhatsAppReminderDelivery $whatsAppDelivery,
        TelegramReminderDelivery $telegramDelivery
    ) {
        $this->deliveryProviders = [
            'whatsapp' => $whatsAppDelivery,
            'telegram' => $telegramDelivery,
        ];
    }

    public function deliverReminder(Reminder $reminder): bool
    {
        $user = $reminder->note->user;
        $reminderMessage = $reminder->message ?? $reminder->note->content;
        $preferredType = $reminder->reminder_type;

        if (!$user instanceof User) {
            Log::error("Invalid user for reminder #{$reminder->id}");
            return false;
        }

        $primaryProvider = $this->deliveryProviders[$preferredType] ?? null;
        if ($primaryProvider && $primaryProvider->isAvailable($user)) {
            $recipient = $this->getRecipientForProvider($user, $preferredType);
            $success = $primaryProvider->sendReminder($recipient, "Reminder", $reminderMessage);
            
            if ($success) {
                Log::info("Sent {$preferredType} reminder #{$reminder->id} to user #{$user->id}");
                return true;
            }
        }

        foreach ($this->deliveryProviders as $type => $provider) {
            if ($type === $preferredType) continue;
            
            if ($provider->isAvailable($user)) {
                $recipient = $this->getRecipientForProvider($user, $type);
                $success = $provider->sendReminder($recipient, "Reminder", $reminderMessage);
                
                if ($success) {
                    Log::info("Sent reminder #{$reminder->id} via {$type} (fallback from {$preferredType}) to user #{$user->id}");
                    return true;
                }
            }
        }

        Log::warning("No delivery method available for reminder #{$reminder->id}, user #{$user->id}");
        return false;
    }

    private function getRecipientForProvider(User $user, string $providerType): string
    {
        return match ($providerType) {
            'whatsapp' => $user->whatsapp_phone,
            'telegram' => $user->telegram_id,
            default => throw new \InvalidArgumentException("Unknown provider type: {$providerType}")
        };
    }

    public function addDeliveryProvider(string $type, ReminderDeliveryInterface $provider): void
    {
        $this->deliveryProviders[$type] = $provider;
    }
}