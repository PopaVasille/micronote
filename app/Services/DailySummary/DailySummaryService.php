<?php

namespace App\Services\DailySummary;

use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DailySummaryService generates structured daily summaries
 * for users containing their relevant tasks, events and reminders
 */
class DailySummaryService
{
    /**
     * Generate a daily summary message for a specific user
     *
     * @param User $user
     * @param Carbon|null $date
     * @return string|null Returns null if no relevant content found
     */
    public function generateDailySummary(User $user, ?Carbon $date = null): ?string
    {
        $date = $date ?: Carbon::now($user->daily_summary_timezone ?? 'Europe/Bucharest');

        Log::channel('trace')->info('DailySummaryService: Generating summary', [
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'timezone' => $user->daily_summary_timezone
        ]);

        $relevantNotes = $this->getRelevantNotesForDate($user, $date);

        if ($relevantNotes->isEmpty()) {
            Log::channel('trace')->info('DailySummaryService: No relevant notes found', [
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d')
            ]);
            return null;
        }

        return $this->formatSummaryMessage($relevantNotes, $date, $user);
    }

    /**
     * Get all notes relevant for the specified date
     * Includes: tasks due today, events today, reminders for today
     *
     * @param User $user
     * @param Carbon $date
     * @return Collection
     */
    private function getRelevantNotesForDate(User $user, Carbon $date): Collection
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Get tasks, events and reminders for today
        return $user->notes()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query
                    // Tasks with due date today (stored in metadata.due_date)
                    ->where(function ($q) use ($startOfDay, $endOfDay) {
                        $q->where('note_type', Note::TYPE_TASK)
                          ->where('is_completed', false)
                          ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.due_date')) >= ?", [$startOfDay->format('Y-m-d')])
                          ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.due_date')) <= ?", [$endOfDay->format('Y-m-d')]);
                    })
                    // Events scheduled for today (stored in metadata.event_date)
                    ->orWhere(function ($q) use ($startOfDay, $endOfDay) {
                        $q->where('note_type', Note::TYPE_EVENT)
                          ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_date')) >= ?", [$startOfDay->format('Y-m-d')])
                          ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_date')) <= ?", [$endOfDay->format('Y-m-d')]);
                    })
                    // Reminders for today (via reminders table)
                    ->orWhere(function ($q) use ($startOfDay, $endOfDay) {
                        $q->where('note_type', Note::TYPE_REMINDER)
                          ->whereIn('id', function ($subQuery) use ($startOfDay, $endOfDay) {
                              $subQuery->select('note_id')
                                       ->from('reminders')
                                       ->where('next_remind_at', '>=', $startOfDay)
                                       ->where('next_remind_at', '<=', $endOfDay)
                                       ->where('is_sent', false);
                          });
                    });
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Format the summary message with structured content
     *
     * @param Collection $notes
     * @param Carbon $date
     * @param User $user
     * @return string
     */
    private function formatSummaryMessage(Collection $notes, Carbon $date, User $user): string
    {
        $greeting = $this->getPersonalizedGreeting($user->name, $date);

        $sections = [];

        // Group notes by type
        $tasks = $notes->where('note_type', Note::TYPE_TASK);
        $events = $notes->where('note_type', Note::TYPE_EVENT);
        $reminders = $notes->where('note_type', Note::TYPE_REMINDER);

        // Build tasks section
        if ($tasks->isNotEmpty()) {
            $sections[] = $this->formatTasksSection($tasks);
        }

        // Build events section
        if ($events->isNotEmpty()) {
            $sections[] = $this->formatEventsSection($events);
        }

        // Build reminders section
        if ($reminders->isNotEmpty()) {
            $sections[] = $this->formatRemindersSection($reminders);
        }

        $footer = "\n📱 _Pentru a gestiona notițele, accesează dashboard-ul web sau trimite-mi un mesaj!_";

        return $greeting . "\n\n" . implode("\n\n", $sections) . $footer;
    }

    /**
     * Generate personalized greeting based on time of day
     *
     * @param string $userName
     * @param Carbon $date
     * @return string
     */
    private function getPersonalizedGreeting(string $userName, Carbon $date): string
    {
        $hour = $date->hour;
        $dayName = $date->locale('ro')->dayName;
        $formattedDate = $date->format('d.m.Y');

        $timeGreeting = match (true) {
            $hour < 12 => 'Bună dimineața',
            $hour < 17 => 'Bună ziua',
            default => 'Bună seara'
        };

        return "🌅 {$timeGreeting}, {$userName}!\n\n📅 Rezumatul pentru {$dayName}, {$formattedDate}";
    }

    /**
     * Format tasks section
     *
     * @param Collection $tasks
     * @return string
     */
    private function formatTasksSection(Collection $tasks): string
    {
        $header = "✅ **Task-uri pentru azi** ({$tasks->count()})";
        $items = [];

        foreach ($tasks as $task) {
            $priority = $this->getPriorityIcon($task->priority);
            $dueTime = $this->extractTimeFromMetadata($task, 'due_time');
            $timeStr = $dueTime ? " ({$dueTime})" : '';

            $items[] = "{$priority} {$task->title} {$timeStr}";
        }

        return $header . "\n" . implode("\n", $items);
    }

    /**
     * Format events section
     *
     * @param Collection $events
     * @return string
     */
    private function formatEventsSection(Collection $events): string
    {
        $header = "📅 **Evenimente** ({$events->count()})";
        $items = [];

        foreach ($events as $event) {
            $eventTime = $this->extractTimeFromMetadata($event, 'event_time');
            $timeStr = $eventTime ? " ({$eventTime})" : '';

            $items[] = "🔸 {$event->title}{$timeStr}";
        }

        return $header . "\n" . implode("\n", $items);
    }

    /**
     * Format reminders section
     *
     * @param Collection $reminders
     * @return string
     */
    private function formatRemindersSection(Collection $reminders): string
    {
        $header = "⏰ **Memento-uri** ({$reminders->count()})";
        $items = [];

        foreach ($reminders as $reminder) {
            // Query reminder data directly instead of using relationship
            $reminderData = Reminder::where('note_id', $reminder->id)
                                  ->where('is_sent', false)
                                  ->orderBy('next_remind_at', 'asc')
                                  ->first();

            $time = $reminderData ? Carbon::parse($reminderData->next_remind_at)->format('H:i') : '';
            $timeStr = $time ? " ({$time})" : '';

            $items[] = "🔔 {$reminder->title}{$timeStr}";
        }

        return $header . "\n" . implode("\n", $items);
    }

    /**
     * Get priority icon based on priority level
     *
     * @param int $priority
     * @return string
     */
    private function getPriorityIcon(int $priority): string
    {
        return match ($priority) {
            3 => '🔥', // High priority
            2 => '⚡', // Medium priority
            default => '▫️' // Normal/low priority
        };
    }

    /**
     * Extract time information from note metadata
     *
     * @param Note $note
     * @param string $timeKey
     * @return string|null
     */
    private function extractTimeFromMetadata(Note $note, string $timeKey): ?string
    {
        if (!$note->metadata || !isset($note->metadata[$timeKey])) {
            return null;
        }

        try {
            return Carbon::parse($note->metadata[$timeKey])->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }
}
