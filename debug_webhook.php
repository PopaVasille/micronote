<?php

require_once 'vendor/autoload.php';

use Telegram\Bot\Api;

$telegram = new Api(getenv('TELEGRAM_BOT_TOKEN') ?: '7413651736:AAH0S7ePqWqJoVLYrE7r5xFTWBGVnHXR6_4');

try {
    $response = $telegram->getWebhookInfo();
    echo "Current webhook info:\n";
    echo "URL: " . $response->get('url') . "\n";
    echo "Has custom certificate: " . ($response->get('has_custom_certificate') ? 'Yes' : 'No') . "\n";
    echo "Pending update count: " . $response->get('pending_update_count') . "\n";
    echo "Last error date: " . $response->get('last_error_date') . "\n";
    echo "Last error message: " . $response->get('last_error_message') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}