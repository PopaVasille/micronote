<?php

namespace App\Services\Telegram\IncomingMessage;

use App\Models\IncomingMessage;
use App\Models\User;
use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\MessageClassificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

readonly class IncomingTelegramMessageProcessorService
{
    /**
     * @param  IncomingMessageRepositoryInterface  $incomingMessageRepository
     */
    public function __construct(
        public IncomingMessageRepositoryInterface $incomingMessageRepository,
        public NoteRepositoryInterface $noteRepository,
        public MessageClassificationService $classificationService
    ){}

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
        $messageDate = $data['message']['date'] ?? null;
        Log::info('$senderIdentifier: ' . $senderIdentifier);
        Log::info('$messageContent: ' . $messageContent);
        Log::info('$messageDate: ' . $messageDate);

        try {

            $user = User::where('telegram_id', $senderIdentifier)->first();
            $userId = $user?->id;
            $noteType = $this->classificationService->classifyMessage($messageContent);
            Log::info("Mesajul a fost clasificat ca: $noteType");
            $incomingMessage = $this->incomingMessageRepository->create([
                'user_id' => $userId,
                'source_type' => IncomingMessage::SOURCE_TYPE_TELEGRAM,
                'sender_identifier' => $senderIdentifier,
                'message_content' => $messageContent,
                'metadata' => json_encode($data),
                'is_processed' => true,
                'processed_at' => now(),
                'ai_tag' => $noteType // Folosim clasificarea regex ca și tag AI
            ]);

            Log::info('Mesaj Telegram salvat în baza de date de către Repo prin service->interface.', ['message_id' => $incomingMessage->id]);

            // Aici, mai târziu, vom adăuga logica de:
            // - Găsire/Creare utilizator după $senderIdentifier
            // - Asociere user_id la $incomingMessage
            // - Trimitere mesajul spre procesare (identificare tip notiță, extragere metadate, etc.)
            // Dacă am găsit un user, creăm și notița
            if ($userId) {
                //todo: de facut cu ai title
                $noteTitle = Str::limit($messageContent, 20, ''); // Primele 50 caractere ca titlu

                // Creăm notița
                $note = $this->noteRepository->create([
                    'user_id' => $userId,
                    'incoming_message_id' => $incomingMessage->id,
                    'title' => $noteTitle,
                    'content' => $messageContent,
                    'note_type' => $noteType,
                    'created_at' => $messageDate ? date('Y-m-d H:i:s', $messageDate) : now(),
                ]);

                // Putem adăuga și tag-ul corespunzător, dar asta vom face mai târziu

                Log::info('Notiță creată cu succes', ['note_id' => $note->id]);
            } else {
                Log::info('Nu s-a găsit un utilizator pentru acest Telegram ID. Se salvează doar mesajul.');
            }

            Log::info('Mesaj Telegram salvat în baza de date.', ['message_id' => $incomingMessage->id]);

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
