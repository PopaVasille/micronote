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
use App\Services\Messaging\NotificationService;
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
     * @param NotificationService $notificationService
     */
    public function __construct(
        public IncomingMessageRepositoryInterface $incomingMessageRepository,
        public NoteRepositoryInterface $noteRepository,
        public HybridMessageClassificationService $classificationService,
        public GeminiClassificationService $geminiService,
        public NotificationService $notificationService
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

            // BIFURCAȚIE: Verificăm planul utilizatorului
            if ($user->isPremium()) {
                Log::channel('trace')->info('UnifiedMessageProcessor: Processing Premium user message', $logContext);
                return $this->processPremiumMessage($user, $messageContent, $rawData, $channelType, $identifier, $correlationId, $logContext);
            } else {
                Log::channel('trace')->info('UnifiedMessageProcessor: Processing Free user message', $logContext);
                return $this->processFreeMessage($user, $messageContent, $rawData, $channelType, $identifier, $correlationId, $logContext);
            }

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
     * Process message for Premium users with multi-action support
     *
     * @param User $user
     * @param string $messageContent
     * @param array $rawData
     * @param string $channelType
     * @param string $identifier
     * @param string $correlationId
     * @param array $logContext
     * @return IncomingMessage|null
     */
    private function processPremiumMessage(
        User $user,
        string $messageContent,
        array $rawData,
        string $channelType,
        string $identifier,
        string $correlationId,
        array $logContext
    ): ?IncomingMessage {
        try {
            // Pas 1: Triajul acțiunilor folosind noul prompt specializat
            $triagedActions = $this->geminiService->triageMultipleActions($messageContent);

            // Fallback la fluxul gratuit dacă triajul eșuează
            if (empty($triagedActions)) {
                Log::warning('Premium AI triage failed - falling back to Free flow', [
                    ...$logContext,
                    'ai_response' => $triagedActions
                ]);
                $incomingMessage = $this->processFreeMessage($user, $messageContent, $rawData, $channelType, $identifier, $correlationId, $logContext);
                $this->sendFallbackMessage($channelType, $identifier, $correlationId);
                return $incomingMessage;
            }

            // Salvarea mesajului original primit
            $incomingMessage = $this->saveIncomingMessage(
                $user->id,
                $channelType,
                $identifier,
                $messageContent,
                $rawData,
                'premium_hybrid', // Tip nou pentru a reflecta noul flux
                $logContext
            );

            if (!$incomingMessage) {
                return null;
            }

            $createdNotes = [];
            $canUseAI = true; // Utilizatorii premium pot folosi mereu AI

            // Pas 2: Procesarea fiecărei acțiuni identificate cu serviciul specializat corespunzător
            foreach ($triagedActions as $action) {
                $actionType = $action['type'];
                $actionText = $action['text'];
                $note = null;

                switch ($actionType) {
                    case 'reminder':
                        $reminderDetails = $this->extractReminderMetadata($actionText, $logContext);
                        if ($reminderDetails) {
                            $noteTitle = $this->generateNoteTitle($reminderDetails['message'], Note::TYPE_REMINDER, $canUseAI, $logContext);
                            $note = $this->createNote($user->id, $incomingMessage->id, $noteTitle, $reminderDetails['message'], Note::TYPE_REMINDER, null, $rawData, $logContext);
                            if ($note) {
                                $this->createReminder($note->id, $reminderDetails, $channelType, $logContext);
                            }
                        }
                        break;

                    case 'shopping_list':
                        $metadata = $this->extractShoppingListMetadata($actionText, $logContext);
                        $noteTitle = $this->generateNoteTitle($actionText, Note::TYPE_SHOPING_LIST, $canUseAI, $logContext);
                        $note = $this->createNote($user->id, $incomingMessage->id, $noteTitle, $actionText, Note::TYPE_SHOPING_LIST, $metadata, $rawData, $logContext);
                        break;

                    case 'task':
                        $taskDetails = $this->extractTaskMetadata($actionText, $logContext);
                        $noteTitle = $this->generateNoteTitle($actionText, Note::TYPE_TASK, $canUseAI, $logContext);
                        $metadata = $taskDetails ? [
                            'due_date' => $taskDetails['due_date'] ?? null,
                            'due_time' => $taskDetails['due_time'] ?? null
                        ] : null;
                        $noteContent = $taskDetails['message'] ?? $actionText;
                        $note = $this->createNote($user->id, $incomingMessage->id, $noteTitle, $noteContent, Note::TYPE_TASK, $metadata, $rawData, $logContext);
                        break;

                    case 'idea':
                    case 'simple':
                    default: // Tratează tipurile necunoscute ca notițe simple
                        $noteTypeConstant = match($actionType) {
                            'idea' => Note::TYPE_IDEA,
                            default => Note::TYPE_SIMPLE,
                        };
                        $noteTitle = $this->generateNoteTitle($actionText, $noteTypeConstant, $canUseAI, $logContext);
                        $note = $this->createNote($user->id, $incomingMessage->id, $noteTitle, $actionText, $noteTypeConstant, null, $rawData, $logContext);
                        break;
                }

                if ($note) {
                    $createdNotes[] = $note;
                }
            }

            if (empty($createdNotes)) {
                 Log::warning('Premium processing finished with no notes created, falling back.', $logContext);
                 return $this->processFreeMessage($user, $messageContent, $rawData, $channelType, $identifier, $correlationId, $logContext);
            }

            // Trimite mesajul de confirmare sumar
            $this->sendPremiumConfirmation($channelType, $identifier, $createdNotes, $correlationId);

            // Actualizează statisticile utilizatorului
            $user->increment('notes_count', count($createdNotes));

            Log::channel('trace')->info('UnifiedMessageProcessor: Premium Hybrid message processing completed successfully', [
                ...$logContext,
                'incoming_message_id' => $incomingMessage->id,
                'notes_created' => count($createdNotes)
            ]);

            return $incomingMessage;

        } catch (\Exception $e) {
            Log::channel('trace')->error('UnifiedMessageProcessor: Premium message processing failed', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Process message for Free users using existing logic
     *
     * @param User $user
     * @param string $messageContent
     * @param array $rawData
     * @param string $channelType
     * @param string $identifier
     * @param string $correlationId
     * @param array $logContext
     * @return IncomingMessage|null
     */
    private function processFreeMessage(
        User $user,
        string $messageContent,
        array $rawData,
        string $channelType,
        string $identifier,
        string $correlationId,
        array $logContext
    ): ?IncomingMessage {
        try {
            // Check AI eligibility
            $canUseAI = $this->classificationService->canUseAI($user);
            $logContext['can_use_ai'] = $canUseAI;
            Log::channel('trace')->info('UnifiedMessageProcessor: AI eligibility checked', $logContext);

            // Classify message type
            $noteType = $this->classificationService->classifyMessage($messageContent, $canUseAI);
            $logContext['note_type_classified'] = $noteType;
            Log::channel('trace')->info("UnifiedMessageProcessor: Message classified as: $noteType", $logContext);

            // Extract metadata based on note type (existing Free logic)
            $metadata = null;
            $reminderDetails = null;
            $taskDetails = null;
            $noteContent = $messageContent;

            if ($noteType === Note::TYPE_SHOPING_LIST) {
                $metadata = $this->extractShoppingListMetadata($messageContent, $logContext);
            } elseif ($noteType === Note::TYPE_REMINDER && $canUseAI) {
                $reminderDetails = $this->extractReminderMetadata($messageContent, $logContext);
                if ($reminderDetails) {
                    $noteContent = $reminderDetails['message'];
                }
            } elseif ($noteType === Note::TYPE_TASK && $canUseAI) {
                $taskDetails = $this->extractTaskMetadata($messageContent, $logContext);
                if ($taskDetails) {
                    $metadata = [
                        'due_date' => $taskDetails['due_date'] ?? null,
                        'due_time' => $taskDetails['due_time'] ?? null
                    ];
                    $noteContent = $taskDetails['message'] ?? $messageContent;
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

            // Send note creation confirmation
            $this->sendNoteCreationConfirmation($channelType, $identifier, $note, $correlationId);

            // Update user statistics
            $user->increment('notes_count');

            Log::channel('trace')->info('UnifiedMessageProcessor: Free message processing completed successfully', [
                ...$logContext,
                'incoming_message_id' => $incomingMessage->id,
                'note_id' => $note->id
            ]);

            return $incomingMessage;

        } catch (\Exception $e) {
            Log::channel('trace')->error('UnifiedMessageProcessor: Free message processing failed', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    

    /**
     * Send premium confirmation message with summary
     *
     * @param string $channelType
     * @param string $identifier
     * @param array $createdNotes
     * @param string $correlationId
     * @return void
     */
    private function sendPremiumConfirmation(
        string $channelType,
        string $identifier,
        array $createdNotes,
        string $correlationId
    ): void {
        try {
            // Group notes by type for summary
            $notesByType = [];
            foreach ($createdNotes as $note) {
                $notesByType[$note->note_type] = ($notesByType[$note->note_type] ?? 0) + 1;
            }

            // Build summary message
            $summaryParts = [];
            foreach ($notesByType as $type => $count) {
                $typeLabel = match($type) {
                    Note::TYPE_REMINDER => $count === 1 ? 'reminder' : 'reminder-uri',
                    Note::TYPE_TASK => $count === 1 ? 'task' : 'task-uri',
                    Note::TYPE_SHOPING_LIST => 'listă de cumpărături',
                    Note::TYPE_IDEA => $count === 1 ? 'idee' : 'idei',
                    Note::TYPE_EVENT => $count === 1 ? 'eveniment' : 'evenimente',
                    Note::TYPE_CONTACT => $count === 1 ? 'contact' : 'contacte',
                    default => $count === 1 ? 'notiță' : 'notițe'
                };

                $summaryParts[] = "$count $typeLabel";
            }

            $summaryText = implode(', ', $summaryParts);
            $message = "✅ Am salvat: $summaryText.";

            $this->notificationService->sendCustomMessage($channelType, $identifier, $message, $correlationId);

        } catch (\Exception $e) {
            Log::channel('trace')->warning('Failed to send premium confirmation', [
                'correlation_id' => $correlationId,
                'channel' => $channelType,
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send fallback message when Premium processing fails
     *
     * @param string $channelType
     * @param string $identifier
     * @param string $correlationId
     * @return void
     */
    private function sendFallbackMessage(
        string $channelType,
        string $identifier,
        string $correlationId
    ): void {
        try {
            $message = "⚠️ Am întâmpinat o problemă la procesarea avansată și am salvat doar prima acțiune. Dacă problema persistă, te rog contactează suportul.";
            $this->notificationService->sendCustomMessage($channelType, $identifier, $message, $correlationId);
        } catch (\Exception $e) {
            Log::channel('trace')->warning('Failed to send fallback message', [
                'correlation_id' => $correlationId,
                'channel' => $channelType,
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
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
            $sourceType = match ($channelType) {
                'telegram' => IncomingMessage::SOURCE_TYPE_TELEGRAM,
                'whatsapp' => IncomingMessage::SOURCE_TYPE_WHATSAPP,
                default => $channelType
            };

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
     * Send note creation confirmation to user
     *
     * @param string $channelType
     * @param string $identifier
     * @param Note $note
     * @param string $correlationId
     * @return void
     */
    private function sendNoteCreationConfirmation(
        string $channelType,
        string $identifier,
        Note $note,
        string $correlationId
    ): void {
        try {
            $this->notificationService->sendNoteCreationConfirmation(
                $channelType,
                $identifier,
                $note,
                $correlationId
            );
        } catch (\Exception $e) {
            // Log error but don't fail the entire process
            Log::channel('trace')->warning('UnifiedMessageProcessor: Failed to send confirmation', [
                'correlation_id' => $correlationId,
                'channel' => $channelType,
                'identifier' => $identifier,
                'note_id' => $note->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract task metadata including due date
     *
     * @param string $messageContent
     * @param array $logContext
     * @return array|null
     */
    private function extractTaskMetadata(string $messageContent, array $logContext): ?array
    {
        Log::channel('trace')->info('UnifiedMessageProcessor: Extracting task details', $logContext);
        $taskDetails = $this->geminiService->extractTaskDetails($messageContent);
        if ($taskDetails) {
            Log::channel('trace')->info('UnifiedMessageProcessor: Task details extracted', [
                ...$logContext,
                'details' => $taskDetails
            ]);
            return $taskDetails;
        }

        Log::channel('trace')->info('UnifiedMessageProcessor: No task details extracted', $logContext);
        return null;
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
