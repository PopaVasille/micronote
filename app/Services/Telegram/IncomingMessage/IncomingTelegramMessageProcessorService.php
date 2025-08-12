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
    public function processTelegramWebhook(array $data, string $correlationId): mixed
    {
        $logContext = ['correlation_id' => $correlationId];
        Log::channel('trace')->info('Processing Telegram webhook data in Service.', array_merge($logContext, ['data' => $data]));

        if (!isset($data['message']['from']['id']) || !isset($data['message']['text'])) {
            Log::channel('trace')->warning('Received invalid Telegram webhook data (missing sender ID or text).', array_merge($logContext, ['data' => $data]));
            return null;
        }

        $senderIdentifier = $data['message']['from']['id'];
        $messageContent = $data['message']['text'];
        $messageDate = $data['message']['date'] ?? null;
        
        $logContext['telegram_sender_id'] = $senderIdentifier;

        Log::channel('trace')->info('Extracted message details.', $logContext);

        try {
            $user = User::where('telegram_id', $senderIdentifier)->first();
            
            if(!$user){
                Log::channel('trace')->warning('User not found for Telegram ID.', $logContext);
                return null;
            }
            
            $userId = $user->id;
            $logContext['user_id'] = $userId;
            Log::channel('trace')->info('User identified.', $logContext);

            $canUseAI = $this->classificationService->canUseAI($user);
            $logContext['can_use_ai'] = $canUseAI;
            Log::channel('trace')->info('Checking AI eligibility.', $logContext);

            $noteType = $this->classificationService->classifyMessage($messageContent, $canUseAI);
            $logContext['note_type_classified'] = $noteType;
            Log::channel('trace')->info("Message classified as: $noteType", $logContext);

            $metadata = null;
            if ($noteType === Note::TYPE_SHOPING_LIST) {
                Log::channel('trace')->info('Extracting shopping list items.', $logContext);
                $items = $this->geminiService->extractShoppingListItems($messageContent);
                if ($items !== null) {
                    $metadata = ['items' => $items];
                    Log::channel('trace')->info('Shopping list items extracted.', array_merge($logContext, ['items' => $items]));
                }
            } elseif ($noteType === Note::TYPE_REMINDER && $canUseAI) {
                Log::channel('trace')->info('Extracting reminder details.', $logContext);
                $reminderDetails = $this->geminiService->extractReminderDetails($messageContent);
                if ($reminderDetails) {
                    $noteContent = $reminderDetails['message'];
                    $noteTitle = Str::limit($noteContent, 20);
                    Log::channel('trace')->info('Reminder details extracted.', array_merge($logContext, ['details' => $reminderDetails]));
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
                'ai_tag' => $noteType
            ]);
            
            $logContext['incoming_message_id'] = $incomingMessage->id;
            Log::channel('trace')->info('Incoming message saved.', $logContext);

            $noteTitle = null;
            if ($canUseAI) {
                Log::channel('trace')->info('Generating note title.', $logContext);
                $noteTitle = $this->geminiService->generateNoteTitle($messageContent, $noteType);
            }
            
            if (!$noteTitle) {
                $noteTitle = Str::limit($messageContent, 20);
                Log::channel('trace')->info('Using fallback for note title.', $logContext);
            } else {
                Log::channel('trace')->info('Note title generated.', array_merge($logContext, ['note_title' => $noteTitle]));
            }

            $note = $this->noteRepository->create([
                'user_id' => $userId,
                'incoming_message_id' => $incomingMessage->id,
                'title' => $noteTitle,
                'content' => $messageContent,
                'note_type' => $noteType,
                'metadata' => $metadata,
                'created_at' => $messageDate ? date('Y-m-d H:i:s', $messageDate) : now(),
            ]);
            
            $logContext['note_id'] = $note->id;
            Log::channel('trace')->info('Note created successfully.', $logContext);

            if ($noteType === Note::TYPE_REMINDER && isset($reminderDetails) && $reminderDetails) {
                Reminder::create([
                    'note_id' => $note->id,
                    'next_remind_at' => $reminderDetails['remind_at'],
                    'recurrence_rule' => $reminderDetails['recurrence_rule'] ?? null,
                    'recurrence_ends_at' => $reminderDetails['recurrence_ends_at'] ?? null,
                    'reminder_type' => 'telegram',
                    'message' => $noteContent ?? null,
                ]);
                Log::channel('trace')->info('Reminder created successfully.', $logContext);
            }
            
            $user->increment('notes_count');

            return $incomingMessage->id;

        } catch (\Exception $e) {
            Log::channel('trace')->error('Error processing Telegram message in Service.', array_merge($logContext, ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]));
            return null;
        }
    }

    // Poți adăuga aici și alte metode legate de procesarea mesajelor primite,
    // cum ar fi "identifyUserFromMessage", "categorizeMessage" etc.
}
