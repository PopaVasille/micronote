<?php

namespace App\Services\Messaging;

use App\Models\IncomingMessage;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\GeminiClassificationService;
use App\Services\Classification\HybridMessageClassificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * UnifiedMessageProcessorService handles message processing for all channels
 * 
 * This service provides a unified interface for processing messages from
 * different messaging channels (Telegram, WhatsApp) with the same logic
 * for AI classification, note creation, and reminder setup.
 */
readonly class UnifiedMessageProcessorService
{
    /**
     * UnifiedMessageProcessorService constructor
     *
     * @param IncomingMessageRepositoryInterface $incomingMessageRepository
     * @param NoteRepositoryInterface $noteRepository
     * @param HybridMessageClassificationService $classificationService
     * @param GeminiClassificationService $geminiService
     */
    public function __construct(
        public IncomingMessageRepositoryInterface $incomingMessageRepository,
        public NoteRepositoryInterface $noteRepository,
        public HybridMessageClassificationService $classificationService,
        public GeminiClassificationService $geminiService
    ) {}

    /**
     * Process an incoming message from any channel
     * 
     * This method replicates the exact logic from IncomingTelegramMessageProcessorService
     * but works for any channel type.
     *
     * @param string $channelType The messaging channel ('telegram' or 'whatsapp')
     * @param string $identifier The user identifier (telegram_id or wa_id)
     * @param string $messageContent The message content
     * @param array $rawData The complete webhook data
     * @param string $correlationId Unique tracking ID
     * @return IncomingMessage|null
     */
    public function processMessage(
        string $channelType,
        string $identifier,
        string $messageContent,
        array $rawData,
        string $correlationId
    ): ?IncomingMessage {
        $logContext = [
            'correlation_id' => $correlationId,
            'channel' => $channelType,
            'identifier' => $identifier
        ];

        Log::channel('trace')->info('UnifiedMessageProcessor: Starting message processing', $logContext);

        try {
            // Find user by channel-specific identifier
            $user = $this->findUserByChannel($channelType, $identifier);
            
            if (!$user) {
                Log::channel('trace')->warning('UnifiedMessageProcessor: User not found', $logContext);
                return null;
            }

            $logContext['user_id'] = $user->id;
            Log::channel('trace')->info('UnifiedMessageProcessor: User identified', $logContext);

            // Check AI eligibility
            $canUseAI = $this->classificationService->canUseAI($user);
            $logContext['can_use_ai'] = $canUseAI;
            Log::channel('trace')->info('UnifiedMessageProcessor: AI eligibility checked', $logContext);

            // Classify message type
            $noteType = $this->classificationService->classifyMessage($messageContent, $canUseAI);
            $logContext['note_type_classified'] = $noteType;
            Log::channel('trace')->info("UnifiedMessageProcessor: Message classified as: $noteType", $logContext);

            // Extract metadata based on note type
            $metadata = null;
            $reminderDetails = null;
            $noteContent = $messageContent;
            
            if ($noteType === Note::TYPE_SHOPING_LIST) {
                $metadata = $this->extractShoppingListMetadata($messageContent, $logContext);
            } elseif ($noteType === Note::TYPE_REMINDER && $canUseAI) {
                $reminderDetails = $this->extractReminderMetadata($messageContent, $logContext);
                if ($reminderDetails) {
                    $noteContent = $reminderDetails['message'];
                }
            }

            // Save incoming message
            $incomingMessage = $this->saveIncomingMessage(
                $user->id,
                $channelType,
                $identifier,
                $messageContent,
                $rawData,
                $noteType,
                $logContext
            );

            if (!$incomingMessage) {
                return null;
            }

            // Generate note title
            $noteTitle = $this->generateNoteTitle($messageContent, $noteType, $canUseAI, $logContext);

            // Create note
            $note = $this->createNote(
                $user->id,
                $incomingMessage->id,
                $noteTitle,
                $noteContent,
                $noteType,
                $metadata,
                $rawData,
                $logContext
            );

            if (!$note) {
                return null;
            }

            // Create reminder if needed
            if ($noteType === Note::TYPE_REMINDER && $reminderDetails) {
                $this->createReminder($note->id, $reminderDetails, $channelType, $logContext);
            }

            // Update user statistics
            $user->increment('notes_count');

            Log::channel('trace')->info('UnifiedMessageProcessor: Message processing completed successfully', [
                ...$logContext,
                'incoming_message_id' => $incomingMessage->id,
                'note_id' => $note->id
            ]);

            return $incomingMessage;

        } catch (\Exception $e) {
            Log::channel('trace')->error('UnifiedMessageProcessor: Message processing failed', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Find user by channel-specific identifier
     *
     * @param string $channelType
     * @param string $identifier
     * @return User|null
     */
    private function findUserByChannel(string $channelType, string $identifier): ?User
    {
        return match ($channelType) {
            'telegram' => User::where('telegram_id', $identifier)->first(),
            'whatsapp' => User::where('whatsapp_id', $identifier)->first(),
            default => null
        };
    }

    /**
     * Extract shopping list metadata using AI
     *
     * @param string $messageContent
     * @param array $logContext
     * @return array|null
     */
    private function extractShoppingListMetadata(string $messageContent, array $logContext): ?array
    {
        Log::channel('trace')->info('UnifiedMessageProcessor: Extracting shopping list items', $logContext);
        
        $items = $this->geminiService->extractShoppingListItems($messageContent);
        
        if ($items !== null) {
            $metadata = ['items' => $items];
            Log::channel('trace')->info('UnifiedMessageProcessor: Shopping list items extracted', [
                ...$logContext,
                'items' => $items
            ]);
            return $metadata;
        }
        
        return null;
    }

    /**
     * Extract reminder metadata using AI
     *
     * @param string $messageContent
     * @param array $logContext
     * @return array|null
     */
    private function extractReminderMetadata(string $messageContent, array $logContext): ?array
    {
        Log::channel('trace')->info('UnifiedMessageProcessor: Extracting reminder details', $logContext);
        
        $reminderDetails = $this->geminiService->extractReminderDetails($messageContent);
        
        if ($reminderDetails) {
            Log::channel('trace')->info('UnifiedMessageProcessor: Reminder details extracted', [
                ...$logContext,
                'details' => $reminderDetails
            ]);
            return $reminderDetails;
        }
        
        return null;
    }

    /**
     * Save incoming message to database
     *
     * @param int $userId
     * @param string $channelType
     * @param string $identifier
     * @param string $messageContent
     * @param array $rawData
     * @param string $noteType
     * @param array $logContext
     * @return IncomingMessage|null
     */
    private function saveIncomingMessage(
        int $userId,
        string $channelType,
        string $identifier,
        string $messageContent,
        array $rawData,
        string $noteType,
        array $logContext
    ): ?IncomingMessage {
        try {
            $sourceType = $channelType === 'telegram' ? IncomingMessage::SOURCE_TYPE_TELEGRAM : $channelType;
            
            $incomingMessage = $this->incomingMessageRepository->create([
                'user_id' => $userId,
                'source_type' => $sourceType,
                'sender_identifier' => $identifier,
                'message_content' => $messageContent,
                'metadata' => json_encode($rawData),
                'is_processed' => true,
                'processed_at' => now(),
                'ai_tag' => $noteType
            ]);

            Log::channel('trace')->info('UnifiedMessageProcessor: Incoming message saved', [
                ...$logContext,
                'incoming_message_id' => $incomingMessage->id
            ]);

            return $incomingMessage;

        } catch (\Exception $e) {
            Log::channel('trace')->error('UnifiedMessageProcessor: Failed to save incoming message', [
                ...$logContext,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate note title using AI or fallback
     *
     * @param string $messageContent
     * @param string $noteType
     * @param bool $canUseAI
     * @param array $logContext
     * @return string
     */
    private function generateNoteTitle(
        string $messageContent,
        string $noteType,
        bool $canUseAI,
        array $logContext
    ): string {
        $noteTitle = null;
        
        if ($canUseAI) {
            Log::channel('trace')->info('UnifiedMessageProcessor: Generating note title with AI', $logContext);
            $noteTitle = $this->geminiService->generateNoteTitle($messageContent, $noteType);
        }

        if (!$noteTitle) {
            $noteTitle = Str::limit($messageContent, 20);
            Log::channel('trace')->info('UnifiedMessageProcessor: Using fallback for note title', $logContext);
        } else {
            Log::channel('trace')->info('UnifiedMessageProcessor: Note title generated', [
                ...$logContext,
                'note_title' => $noteTitle
            ]);
        }

        return $noteTitle;
    }

    /**
     * Create note in database
     *
     * @param int $userId
     * @param int $incomingMessageId
     * @param string $noteTitle
     * @param string $noteContent
     * @param string $noteType
     * @param array|null $metadata
     * @param array $rawData
     * @param array $logContext
     * @return Note|null
     */
    private function createNote(
        int $userId,
        int $incomingMessageId,
        string $noteTitle,
        string $noteContent,
        string $noteType,
        ?array $metadata,
        array $rawData,
        array $logContext
    ): ?Note {
        try {
            // Extract message date based on channel
            $messageDate = $this->extractMessageDate($rawData);
            
            $note = $this->noteRepository->create([
                'user_id' => $userId,
                'incoming_message_id' => $incomingMessageId,
                'title' => $noteTitle,
                'content' => $noteContent,
                'note_type' => $noteType,
                'metadata' => $metadata,
                'created_at' => $messageDate ? date('Y-m-d H:i:s', $messageDate) : now(),
            ]);

            Log::channel('trace')->info('UnifiedMessageProcessor: Note created successfully', [
                ...$logContext,
                'note_id' => $note->id
            ]);

            return $note;

        } catch (\Exception $e) {
            Log::channel('trace')->error('UnifiedMessageProcessor: Failed to create note', [
                ...$logContext,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create reminder in database
     *
     * @param int $noteId
     * @param array $reminderDetails
     * @param string $channelType
     * @param array $logContext
     * @return void
     */
    private function createReminder(
        int $noteId,
        array $reminderDetails,
        string $channelType,
        array $logContext
    ): void {
        try {
            Reminder::create([
                'note_id' => $noteId,
                'next_remind_at' => $reminderDetails['remind_at'],
                'recurrence_rule' => $reminderDetails['recurrence_rule'] ?? null,
                'recurrence_ends_at' => $reminderDetails['recurrence_ends_at'] ?? null,
                'reminder_type' => $channelType,
                'message' => $reminderDetails['message'] ?? null,
            ]);

            Log::channel('trace')->info('UnifiedMessageProcessor: Reminder created successfully', $logContext);

        } catch (\Exception $e) {
            Log::channel('trace')->error('UnifiedMessageProcessor: Failed to create reminder', [
                ...$logContext,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract message date from raw webhook data
     *
     * @param array $rawData
     * @return int|null Unix timestamp
     */
    private function extractMessageDate(array $rawData): ?int
    {
        // For Telegram
        if (isset($rawData['message']['date'])) {
            return $rawData['message']['date'];
        }

        // For WhatsApp - timestamp is usually in different format
        if (isset($rawData['entry'][0]['changes'][0]['value']['messages'][0]['timestamp'])) {
            return (int) $rawData['entry'][0]['changes'][0]['value']['messages'][0]['timestamp'];
        }

        return null;
    }
}