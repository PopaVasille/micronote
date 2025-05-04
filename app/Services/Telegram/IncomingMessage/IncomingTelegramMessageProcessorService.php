<?php

namespace App\Services\Telegram\IncomingMessage;

use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use Illuminate\Support\Facades\Log;

readonly class IncomingTelegramMessageProcessorService
{
    /**
     * @param  IncomingMessageRepositoryInterface  $incomingMessageRepository
     */
    public function __construct(public IncomingMessageRepositoryInterface $incomingMessageRepository){}

    /**
     * @param  array  $data
     * @return int|mixed|null
     */
    public function processTelegramWebhook(array $data): mixed
    {
        Log::info('Processing Telegram webhook data in Service.', $data);

        // Validăm rapid dacă datele esențiale există
        if (!isset($data['message']['from']['id']) || !isset($data['message']['text'])) {
            Log::warning('Received invalid Telegram webhook data (missing sender ID or text).', $data);
            return null; // Sau poți arunca o excepție specifică
        }

        $senderIdentifier = $data['message']['from']['id'];
        $messageContent = $data['message']['text'];
        Log::info('$senderIdentifier: ' . $senderIdentifier);
        Log::info('$messageContent: ' . $messageContent);

        try {

            $incomingMessage = $this->incomingMessageRepository->create([
                // 'user_id' => null, // Încă nu știm cine e utilizatorul
                'source_type' => 'telegram',
                'sender_identifier' => $senderIdentifier,
                'message_content' => $messageContent,
                'metadata' => json_encode($data), // Salvăm tot request-ul brut
                'is_processed' => false,
            ]);

            Log::info('Mesaj Telegram salvat în baza de date de către Repo prin service->interface.', ['message_id' => $incomingMessage->id]);

            // Aici, mai târziu, vom adăuga logica de:
            // - Găsire/Creare utilizator după $senderIdentifier
            // - Asociere user_id la $incomingMessage
            // - Trimitere mesajul spre procesare (identificare tip notiță, extragere metadate, etc.)

            return $incomingMessage->id;

        } catch (\Exception $e) {
            Log::error('Error saving Telegram message in Service:', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return null;
        }
    }

    // Poți adăuga aici și alte metode legate de procesarea mesajelor primite,
    // cum ar fi "identifyUserFromMessage", "categorizeMessage" etc.
}
