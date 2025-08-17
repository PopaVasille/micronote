<?php

namespace App\Jobs;

use App\Services\Messaging\UnifiedMessageProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessIncomingMessageJob handles asynchronous processing of incoming messages
 * 
 * This job processes messages from both Telegram and WhatsApp channels,
 * performing AI classification, note creation, and reminder setup.
 */
class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run
     */
    public int $timeout = 120;

    /**
     * ProcessIncomingMessageJob constructor
     *
     * @param string $channelType The messaging channel ('telegram' or 'whatsapp')
     * @param string $identifier The user identifier (telegram_id or wa_id)
     * @param string $messageContent The actual message content
     * @param array $rawData The complete webhook data
     * @param string $correlationId Unique ID for tracking this message
     */
    public function __construct(
        private string $channelType,
        private string $identifier,
        private string $messageContent,
        private array $rawData,
        private string $correlationId
    ) {
        // Set queue based on channel type for load balancing
        $this->onQueue($channelType . '_messages');
    }

    /**
     * Execute the job
     *
     * @param UnifiedMessageProcessorService $processor
     * @return void
     */
    public function handle(UnifiedMessageProcessorService $processor): void
    {
        $logContext = [
            'job_id' => $this->job->getJobId(),
            'correlation_id' => $this->correlationId,
            'channel' => $this->channelType,
            'identifier' => $this->identifier,
            'attempt' => $this->attempts()
        ];

        Log::channel('trace')->info('ProcessIncomingMessageJob: Starting message processing', $logContext);

        try {
            // Process the message through unified service
            $result = $processor->processMessage(
                $this->channelType,
                $this->identifier,
                $this->messageContent,
                $this->rawData,
                $this->correlationId
            );

            if ($result) {
                Log::channel('trace')->info('ProcessIncomingMessageJob: Message processed successfully', [
                    ...$logContext,
                    'incoming_message_id' => $result->id ?? 'unknown'
                ]);
            } else {
                Log::channel('trace')->warning('ProcessIncomingMessageJob: Message processing returned null', $logContext);
            }

        } catch (\Exception $e) {
            Log::channel('trace')->error('ProcessIncomingMessageJob: Message processing failed', [
                ...$logContext,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('trace')->error('ProcessIncomingMessageJob: Job failed after all retries', [
            'correlation_id' => $this->correlationId,
            'channel' => $this->channelType,
            'identifier' => $this->identifier,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Could trigger notification to admin here
        // or add to dead letter queue for manual review
    }

    /**
     * Get the tags that should be assigned to the job
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'messaging',
            $this->channelType,
            'correlation:' . $this->correlationId
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     *
     * @return array
     */
    public function backoff(): array
    {
        // Exponential backoff: 10s, 30s, 90s
        return [10, 30, 90];
    }
}