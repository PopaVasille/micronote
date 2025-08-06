<?php

namespace App\Services\Telegram\IncomingMessage;

use App\Models\IncomingMessage;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\GeminiClassificationService;
use App\Services\Classification\HybridMessageClassificationService;
use App\Services\Classification\MessageClassificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

readonly class IncomingTelegramMessageProcessorService
{
    /**
     * @param  IncomingMessageRepositoryInterface  $incomingMessageRepository
     * @param  NoteRepositoryInterface  $noteRepository
     * @param  HybridMessageClassificationService  $classificationService
     */
    public function __construct(
        public IncomingMessageRepositoryInterface $incomingMessageRepository,
        public NoteRepositoryInterface $noteRepository,
        public HybridMessageClassificationService $classificationService,
        public GeminiClassificationService $geminiService
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
            Log::info('$userId: ' . $userId);
            if($user){
                $canUseAI = $this->classificationService->canUseAI($user);
                $noteType = $this->classificationService->classifyMessage($messageContent,$canUseAI);
                Log::info("Message classified as: $noteType using " . ($canUseAI ? 'AI+Regex' : 'Regex only'));
                Log::info("Mesajul a fost clasificat ca: $noteType");

                $metadata = null;
                // Dacă notița este o listă de cumpărături, extragem itemii
                if ($noteType === Note::TYPE_SHOPING_LIST) {
                    $items = $this->geminiService->extractShoppingListItems($messageContent);
                    if ($items !== null) {
                        $metadata = ['items' => $items];
                    }
                }elseif ($noteType === Note::TYPE_REMINDER && $canUseAI) {
                    $reminderDetails = $this->geminiService->extractReminderDetails($messageContent);
                    if ($reminderDetails) {
                        $noteContent = $reminderDetails['message'];
                        $noteTitle = Str::limit($noteContent, 20);
                        // Vom crea reminder-ul mai jos, după ce avem nota
                    }
                }
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

                //ToDo: de facut cu AI titlu
                $noteTitle = Str::limit($messageContent, 20);
                // Aici, mai târziu, vom adăuga logica de:
                // - Găsire/Creare utilizator după $senderIdentifier
                // - Asociere user_id la $incomingMessage
                // - Trimitere mesajul spre procesare (identificare tip notiță, extragere metadate, etc.)
                // Dacă am găsit un user, creăm și notița

                    // Creăm notița
                $note = $this->noteRepository->create([
                    'user_id' => $userId,
                    'incoming_message_id' => $incomingMessage->id,
                    'title' => $noteTitle,
                    'content' => $messageContent,
                    'note_type' => $noteType,
                    'metadata' => $metadata,
                    'created_at' => $messageDate ? date('Y-m-d H:i:s', $messageDate) : now(),
                ]);
                // Crearea reminderului dacă este cazul
                if ($noteType === Note::TYPE_REMINDER && isset($reminderDetails) && $reminderDetails) {
                    Reminder::create([
                        'note_id' => $note->id,
                        'next_remind_at' => $reminderDetails['remind_at'],
                        'recurrence_rule' => $reminderDetails['recurrence_rule'] ?? null,
                        'recurrence_ends_at' => $reminderDetails['recurrence_ends_at'] ?? null,
                        'reminder_type' => 'telegram', // Sau din preferințele userului
                        'message' => $noteContent ?? null,
                    ]);
                    Log::info('Reminder creat cu succes pentru notița ' . $note->id);
                }
                $user->increment('notes_count');
                // Putem adăuga și tag-ul corespunzător, dar asta vom face mai târziu

                Log::info('Notiță creată cu succes', ['note_id' => $note->id]);

                return $incomingMessage->id;
            } else {
                Log::info('Nu s-a găsit un utilizator pentru ID-ul Telegram: ' . $senderIdentifier);
                return null;
            }

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
