<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DailySummary\DailySummaryService;
use App\Services\Messaging\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendDailySummariesJob processes daily summaries for all eligible users
 * This job is designed to run once per day and handle all users efficiently
 */
class SendDailySummariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run
     */
    public int $timeout = 600; // 10 minutes for all users

    /**
     * Execute the job - process all users with daily summaries enabled
     */
    public function handle(
        DailySummaryService $summaryService, 
        NotificationService $notificationService
    ): void {
        $jobId = $this->job ? $this->job->getJobId() : 'manual';
        $correlationId = 'daily-summary-' . date('Y-m-d') . '-' . $jobId;

        Log::channel('trace')->info('SendDailySummariesJob: Starting daily summary processing', [
            'job_id' => $jobId,
            'correlation_id' => $correlationId,
            'attempt' => $this->attempts()
        ]);

        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;

        try {
            $currentUtcHour = now()->utc()->hour;
            
            // Get all users with daily summaries enabled who should receive it at this UTC hour
            $users = User::where('daily_summary_enabled', true)
                ->where('is_active', true)
                ->whereNotNull('name')
                ->get()
                ->filter(function (User $user) use ($currentUtcHour) {
                    return $this->shouldSendSummaryNow($user, $currentUtcHour);
                });

            Log::channel('trace')->info('SendDailySummariesJob: Found eligible users', [
                'correlation_id' => $correlationId,
                'total_users' => $users->count()
            ]);

            foreach ($users as $user) {
                $processedCount++;
                
                try {
                    $this->processUserSummary($user, $summaryService, $notificationService, $correlationId);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::channel('trace')->error('SendDailySummariesJob: Failed to process user summary', [
                        'correlation_id' => $correlationId,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue processing other users even if one fails
                }
            }

            Log::channel('trace')->info('SendDailySummariesJob: Completed daily summary processing', [
                'correlation_id' => $correlationId,
                'processed' => $processedCount,
                'success' => $successCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::channel('trace')->error('SendDailySummariesJob: Critical error in daily summary job', [
                'correlation_id' => $correlationId,
                'processed' => $processedCount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Process daily summary for a single user
     */
    private function processUserSummary(
        User $user, 
        DailySummaryService $summaryService, 
        NotificationService $notificationService,
        string $correlationId
    ): void {
        $userCorrelationId = $correlationId . '-user-' . $user->id;

        Log::channel('trace')->info('SendDailySummariesJob: Processing user summary', [
            'correlation_id' => $userCorrelationId,
            'user_id' => $user->id,
            'timezone' => $user->daily_summary_timezone
        ]);

        // Generate summary for user's timezone
        $userDate = Carbon::now($user->daily_summary_timezone ?? 'Europe/Bucharest');
        $summaryContent = $summaryService->generateDailySummary($user, $userDate);

        if ($summaryContent === null) {
            Log::channel('trace')->info('SendDailySummariesJob: No content for user, skipping', [
                'correlation_id' => $userCorrelationId,
                'user_id' => $user->id
            ]);
            return; // No relevant content for today
        }

        // Determine preferred communication channel and send message
        $sent = $this->sendSummaryToUser($user, $summaryContent, $notificationService, $userCorrelationId);

        if ($sent) {
            Log::channel('trace')->info('SendDailySummariesJob: Summary sent successfully', [
                'correlation_id' => $userCorrelationId,
                'user_id' => $user->id
            ]);
        } else {
            Log::channel('trace')->warning('SendDailySummariesJob: Failed to send summary', [
                'correlation_id' => $userCorrelationId,
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Send summary to user via their preferred channel
     */
    private function sendSummaryToUser(
        User $user, 
        string $summaryContent, 
        NotificationService $notificationService,
        string $correlationId
    ): bool {
        // Determine preferred channel (prioritize Telegram, fallback to WhatsApp)
        if ($user->telegram_id) {
            return $this->sendViaTelegram($user, $summaryContent, $notificationService, $correlationId);
        } elseif ($user->whatsapp_id || $user->whatsapp_phone) {
            return $this->sendViaWhatsApp($user, $summaryContent, $notificationService, $correlationId);
        }

        Log::channel('trace')->warning('SendDailySummariesJob: User has no messaging channels configured', [
            'correlation_id' => $correlationId,
            'user_id' => $user->id
        ]);
        return false;
    }

    /**
     * Send summary via Telegram
     */
    private function sendViaTelegram(
        User $user, 
        string $summaryContent, 
        NotificationService $notificationService,
        string $correlationId
    ): bool {
        return $notificationService->sendCustomMessage(
            'telegram',
            $user->telegram_id,
            $summaryContent,
            $correlationId
        );
    }

    /**
     * Send summary via WhatsApp
     */
    private function sendViaWhatsApp(
        User $user, 
        string $summaryContent, 
        NotificationService $notificationService,
        string $correlationId
    ): bool {
        $phoneNumber = $user->whatsapp_phone ?? $user->whatsapp_id;
        
        return $notificationService->sendCustomMessage(
            'whatsapp',
            $phoneNumber,
            $summaryContent,
            $correlationId
        );
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('trace')->error('SendDailySummariesJob: Job failed after all retries', [
            'date' => date('Y-m-d'),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     */
    public function backoff(): array
    {
        return [300, 900, 1800]; // 5min, 15min, 30min
    }

    /**
     * Check if we should send summary to this user at current UTC hour
     */
    private function shouldSendSummaryNow(User $user, int $currentUtcHour): bool
    {
        try {
            $userTimezone = $user->daily_summary_timezone ?? 'Europe/Bucharest';
            $userSummaryTime = $user->daily_summary_time ?? '08:00';
            
            // Parse user's configured time (e.g., "08:00")
            [$hour, $minute] = explode(':', $userSummaryTime);
            $userHour = (int) $hour;
            
            // Create a datetime in user's timezone for their configured time
            $userDateTime = Carbon::now($userTimezone)
                ->setTime($userHour, (int) $minute, 0);
            
            // Convert to UTC to compare with current UTC hour
            $userUtcHour = $userDateTime->utc()->hour;
            
            return $userUtcHour === $currentUtcHour;
            
        } catch (\Exception $e) {
            Log::channel('trace')->warning('SendDailySummariesJob: Error checking user schedule', [
                'user_id' => $user->id,
                'timezone' => $user->daily_summary_timezone,
                'time' => $user->daily_summary_time,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return [
            'daily-summary',
            'scheduled',
            'date:' . date('Y-m-d')
        ];
    }
}
