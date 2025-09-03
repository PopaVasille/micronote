<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppReminderService
{
    private string $accessToken;
    private string $phoneNumberId;
    private string $apiVersion;

    public function __construct()
    {
        $this->accessToken = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.from-phone-number-id');
        $this->apiVersion = config('services.whatsapp.api_version', 'v23.0');
    }

    public function sendReminderTemplate(string $recipientPhone, string $reminderTitle, string $reminderContent): bool
    {
        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $response = Http::withToken($this->accessToken)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => 'demo_memento',
                        'language' => [
                            'code' => 'ro'
                        ],
                        'components' => [
                            [
                                'type' => 'body',
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $reminderTitle
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $reminderContent
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp reminder sent successfully to {$recipientPhone}");
                return true;
            } else {
                Log::error("Failed to send WhatsApp reminder", [
                    'phone' => $recipientPhone,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Exception sending WhatsApp reminder: " . $e->getMessage(), [
                'phone' => $recipientPhone,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Send simple text message via WhatsApp
     *
     * @param string $recipientPhone
     * @param string $message
     * @return bool
     */
    public function sendSimpleTextMessage(string $recipientPhone, string $message): bool
    {
        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

            $response = Http::withToken($this->accessToken)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]);

            if ($response->successful()) {
                Log::info("WhatsApp text message sent successfully to {$recipientPhone}");
                return true;
            } else {
                Log::error("Failed to send WhatsApp text message", [
                    'phone' => $recipientPhone,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Exception sending WhatsApp text message: " . $e->getMessage(), [
                'phone' => $recipientPhone,
                'exception' => $e
            ]);
            return false;
        }
    }
}
