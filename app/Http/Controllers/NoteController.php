<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Reminder;
use App\Http\Requests\UpdateNoteRequest;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\MessageClassificationService;
use App\Services\DailySummary\DailySummaryService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class NoteController extends Controller
{
    public function __construct(readonly NoteRepositoryInterface $noteRepository){}


    /**
     * Display the dashboard with user notes.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $searchQuery = $request->query('search');
        $sortDirection = $request->query('sort', 'desc');

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $notes = match($filter) {
            'all' => $this->noteRepository->getAllByUserId($user->id, $searchQuery, $sortDirection),
            'favorite' => $this->noteRepository->getFavoriteByUserId($user->id, $searchQuery, $sortDirection),
            'completed' => $this->noteRepository->getCompletedByUserId($user->id, $searchQuery, $sortDirection),
            default => $this->noteRepository->getByUserIdAndType($user->id, $filter, $searchQuery, $sortDirection)
        };

        Log::info('$user: '.$user);
        Log::info('$notes: '.$notes);

        return Inertia::render('Dashboard', [
            'notes' => $notes,
            'filter' => $filter,
            'search' => $searchQuery,
            'sort' => $sortDirection,
        ])->withViewData([
            'cache-control' => 'max-age=60, must-revalidate' // Cache pentru 60 secunde, dar trebuie revalidat
        ]);
    }

    /**
     * Store a new note(manual store).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'note_type' => 'nullable|string|in:simple,task,idea,shopping_list'
        ]);

        $user = $request->user();
        $validated['user_id'] = $user->id;

        // Verificăm limita de notițe pentru utilizatorii free
        if ($user->plan === 'free' && $user->notes_count >= $user->monthly_notes_limit) {
            return redirect()->back()
                ->with('error', 'Ai atins limita de notițe pentru contul free. Actualizează la planul Plus pentru notițe nelimitate.');
        }

        if (!isset($validated['note_type']) || $validated['note_type'] === 'simple') {
            $classificationService = app(MessageClassificationService::class);
            $validated['note_type'] = $classificationService->classifyMessage($validated['content']);
        }

        // Creăm notița
        $note = $this->noteRepository->create($validated);

        // Incrementăm contorul de notițe al utilizatorului
        $user->increment('notes_count');

        // Răspuns diferit pentru API vs. web
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'note' => $note,
                'message' => 'Notiță creată cu succes!'
            ]);
        }

        // Redirect către dashboard cu un mesaj de succes
        return redirect()->route('dashboard')
        ->with('success', 'Notiță creată cu succes!');
    }

    /**
     * Update a note's status.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validated();

        $updatedNote = $this->noteRepository->update($note, $validated);

        return Inertia::render('Dashboard', [
            'updatedNote' => $updatedNote,
            'successMessage' => 'Notiță actualizată cu succes!',
        ])->with('banner', 'Notiță actualizată cu succes!');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note)
    {
        // Verify that the user owns this note
        $this->authorize('view', $note);

        // Return note data as JSON for API requests
        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'note_type' => $note->note_type,
            'priority' => $note->priority,
            'is_completed' => $note->is_completed,
            'is_favorite' => $note->is_favorite,
            'metadata' => $note->metadata,
            'created_at' => $note->created_at->toISOString(),
            'updated_at' => $note->updated_at->toISOString(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {
        //
    }
    public function toggleFavorite(Request $request, Note $note)
    {
        Log::info('Toggle favorite pentru notița: ' . $note->id, [
            'user_id' => auth()->id(),
            'note_user_id' => $note->user_id,
            'current_favorite' => $note->is_favorite
        ]);

        // Verifică că utilizatorul este proprietarul notiței
        $this->authorize('update', $note);

        // Toggle favorite
        $note->update([
            'is_favorite' => !$note->is_favorite
        ]);

        Log::info('Favorite toggleat cu succes', [
            'note_id' => $note->id,
            'new_favorite_status' => $note->is_favorite
        ]);

        // Returnează back cu mesaj de succes
        return back()->with('success', 'Notiță actualizată cu succes');
    }

    /**
     * Toggle completed status pentru task-uri
     */
    public function toggleCompleted(Request $request, Note $note)
    {
        Log::info('Toggle completed pentru notița: ' . $note->id);

        // Verifică că utilizatorul este proprietarul notiței
        $this->authorize('update', $note);

        // Toggle completed
        $note->update([
            'is_completed' => !$note->is_completed
        ]);

        return back()->with('success', 'Status task actualizat cu succes');
    }
    /**
     * Update the entire shopping list for a note.
     *
     * @param Request $request
     * @param Note $note
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateShoppingList(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        if ($note->note_type !== 'shopping_list') {
            return back()->with('banner.danger', 'Notița nu este o listă de cumpărături.');
        }

        // Validate the incoming data to ensure it's a proper array of items
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.text' => ['required', 'string', 'max:255'],
            'items.*.completed' => ['required', 'boolean'],
        ]);

        $this->noteRepository->update($note, ['metadata' => ['items' => $validated['items']]]);

        return back()->with('banner', 'Itemele au fost actualizat!');
    }

    /**
     * Get daily summary data for dashboard
     */
    public function getDailySummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = Carbon::now($user->daily_summary_timezone ?? 'Europe/Bucharest');
        
        // Get tasks due today
        $tasksToday = $user->notes()
            ->where('note_type', Note::TYPE_TASK)
            ->where('is_completed', false)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.due_date')) = ?", [$date->format('Y-m-d')])
            ->orderByDesc('priority')
            ->limit(3)
            ->get(['id', 'title', 'priority', 'metadata']);

        // Get events today
        $eventsToday = $user->notes()
            ->where('note_type', Note::TYPE_EVENT)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_date')) = ?", [$date->format('Y-m-d')])
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_time'))")
            ->limit(3)
            ->get(['id', 'title', 'metadata']);

        // Count total tasks and events for today
        $totalTasks = $user->notes()
            ->where('note_type', Note::TYPE_TASK)
            ->where('is_completed', false)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.due_date')) = ?", [$date->format('Y-m-d')])
            ->count();

        $totalEvents = $user->notes()
            ->where('note_type', Note::TYPE_EVENT)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_date')) = ?", [$date->format('Y-m-d')])
            ->count();

        return response()->json([
            'tasks_today' => $totalTasks,
            'events_today' => $totalEvents,
            'items' => [
                'tasks' => $tasksToday->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'priority' => $task->priority,
                        'due_time' => $task->metadata['due_time'] ?? null,
                        'type' => 'task'
                    ];
                }),
                'events' => $eventsToday->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'event_time' => $event->metadata['event_time'] ?? null,
                        'type' => 'event'
                    ];
                })
            ]
        ]);
    }

    /**
     * Get active reminders for dashboard
     */
    public function getActiveReminders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized access',
                    'message' => 'User authentication required'
                ], 401);
            }
            
            // Get next 4 upcoming reminders
            $activeReminders = Reminder::whereHas('note', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('note_type', Note::TYPE_REMINDER);
                })
                ->where('is_sent', false)
                ->where('next_remind_at', '>', now())
                ->orderBy('next_remind_at')
                ->with(['note:id,title'])
                ->limit(4)
                ->get(['id', 'note_id', 'next_remind_at', 'message']);

            return response()->json(
                $activeReminders->map(function ($reminder) {
                    return [
                        'id' => $reminder->id,
                        'note_id' => $reminder->note_id,
                        'title' => $reminder->note->title ?? $reminder->message ?? 'Untitled Reminder',
                        'remind_at' => $reminder->next_remind_at->format('Y-m-d H:i'),
                        'remind_at_human' => $this->formatRemindTime($reminder->next_remind_at),
                    ];
                })
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in getActiveReminders', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'error' => 'Database error',
                'message' => 'Unable to retrieve reminders due to database issues'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Unexpected error in getActiveReminders', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'An unexpected error occurred while retrieving reminders'
            ], 500);
        }
    }

    /**
     * Mark a reminder as completed (complete associated note)
     */
    public function completeReminder(Request $request, int $reminderId)
    {
        $user = $request->user();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'User authentication required'
                ], 401);
            }
            return back()->withErrors(['message' => 'Sesiunea a expirat. Reîncarcă pagina.']);
        }

        // Validate reminder ID
        if (!is_numeric($reminderId) || $reminderId <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Invalid reminder ID',
                    'message' => 'Reminder ID must be a positive integer'
                ], 400);
            }
            return back()->withErrors(['message' => 'ID-ul mementoului nu este valid.']);
        }

        try {
            $completedReminder = null;
            
            \DB::transaction(function () use ($user, $reminderId, &$completedReminder) {
                $reminder = Reminder::whereHas('note', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->lockForUpdate()
                    ->find($reminderId);

                if (!$reminder) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Reminder not found');
                }

                // Mark the associated note as completed
                if ($reminder->note && !$reminder->note->is_completed) {
                    $reminder->note->update(['is_completed' => true]);
                }

                // Store reminder info before deletion
                $completedReminder = [
                    'id' => $reminder->id,
                    'title' => $reminder->note->title ?? $reminder->message ?? 'Unknown'
                ];

                // Delete the reminder to stop future notifications
                $reminder->delete();
            }, 3); // 3 attempts for deadlock handling

            Log::info('Reminder completed successfully', [
                'reminder_id' => $reminderId,
                'user_id' => $user->id,
                'reminder_title' => $completedReminder['title']
            ]);

            // For JSON requests (API), return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Memento marcat ca finalizat!',
                    'reminder_id' => $reminderId
                ]);
            }

            // For Inertia.js requests, return a redirect back with success message
            return back()->with('success', 'Memento marcat ca finalizat!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Reminder not found for completion', [
                'reminder_id' => $reminderId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Reminder not found',
                    'message' => 'Mementoul nu a fost găsit sau nu îți aparține.'
                ], 404);
            }
            return back()->withErrors(['message' => 'Mementoul nu a fost găsit sau nu îți aparține.']);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error completing reminder', [
                'reminder_id' => $reminderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            $message = 'Eroare de bază de date. Încearcă din nou în câteva momente.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Database error',
                    'message' => $message
                ], 500);
            }
            return back()->withErrors(['message' => $message]);

        } catch (\Illuminate\Database\DeadlockException $e) {
            Log::warning('Deadlock detected while completing reminder', [
                'reminder_id' => $reminderId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Mementoul este în curs de procesare. Încearcă din nou în câteva secunde.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Concurrent operation',
                    'message' => $message
                ], 409);
            }
            return back()->withErrors(['message' => $message]);

        } catch (\Exception $e) {
            Log::error('Unexpected error completing reminder', [
                'reminder_id' => $reminderId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'A apărut o eroare neașteptată la finalizarea mementoului.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Server error',
                    'message' => $message
                ], 500);
            }
            return back()->withErrors(['message' => $message]);
        }
    }

    /**
     * Get active tasks for dashboard
     */
    public function getActiveTasks(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized access',
                    'message' => 'User authentication required'
                ], 401);
            }

            $today = Carbon::now($user->daily_summary_timezone ?? 'Europe/Bucharest')->format('Y-m-d');
            
            // Get tasks with due date = today and is_completed = false
            $tasksWithDueDate = $user->notes()
                ->where('note_type', Note::TYPE_TASK)
                ->where('is_completed', false)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.due_date')) = ?", [$today])
                ->orderByDesc('priority')
                ->orderBy('created_at', 'desc')
                ->limit(50) // Prevent excessive data transfer
                ->get(['id', 'title', 'priority', 'metadata', 'created_at', 'updated_at']);

            // Get tasks with due_date = null and is_completed = false
            $tasksWithoutDueDate = $user->notes()
                ->where('note_type', Note::TYPE_TASK)
                ->where('is_completed', false)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.due_date')) IS NULL")
                ->orderByDesc('priority')
                ->orderBy('created_at', 'desc')
                ->limit(50) // Prevent excessive data transfer
                ->get(['id', 'title', 'priority', 'metadata', 'created_at', 'updated_at']);

            return response()->json([
                'tasks_with_due_date' => $tasksWithDueDate->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title ?? 'Untitled Task',
                        'priority' => $task->priority ?? 1,
                        'due_date' => $task->metadata['due_date'] ?? null,
                        'due_time' => $task->metadata['due_time'] ?? null,
                        'created_at' => $task->created_at->toISOString(),
                        'updated_at' => $task->updated_at->toISOString(),
                        'note_type' => 'task'
                    ];
                }),
                'tasks_without_due_date' => $tasksWithoutDueDate->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title ?? 'Untitled Task',
                        'priority' => $task->priority ?? 1,
                        'due_date' => null,
                        'due_time' => null,
                        'created_at' => $task->created_at->toISOString(),
                        'updated_at' => $task->updated_at->toISOString(),
                        'note_type' => 'task'
                    ];
                }),
                'total_count' => $tasksWithDueDate->count() + $tasksWithoutDueDate->count(),
                'success' => true
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in getActiveTasks', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'error' => 'Database error',
                'message' => 'Unable to retrieve tasks due to database issues'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Unexpected error in getActiveTasks', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Server error',
                'message' => 'An unexpected error occurred while retrieving tasks'
            ], 500);
        }
    }

    /**
     * Mark a task as completed
     */
    public function completeTask(Request $request, int $taskId)
    {
        $user = $request->user();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'User authentication required'
                ], 401);
            }
            return back()->withErrors(['message' => 'Sesiunea a expirat. Reîncarcă pagina.']);
        }

        // Validate task ID
        if (!is_numeric($taskId) || $taskId <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Invalid task ID',
                    'message' => 'Task ID must be a positive integer'
                ], 400);
            }
            return back()->withErrors(['message' => 'ID-ul task-ului nu este valid.']);
        }
        
        try {
            $completedTask = null;
            
            \DB::transaction(function () use ($user, $taskId, &$completedTask) {
                $task = Note::where('user_id', $user->id)
                           ->where('id', $taskId)
                           ->where('note_type', Note::TYPE_TASK)
                           ->where('is_completed', false)
                           ->lockForUpdate()
                           ->first();
                
                if (!$task) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Task not found or already completed');
                }
                
                $task->update(['is_completed' => true]);
                $completedTask = $task;
            }, 3); // 3 attempts for deadlock handling

            Log::info('Task completed successfully', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'task_title' => $completedTask->title ?? 'Unknown'
            ]);

            // For JSON requests (API), return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task marcat ca finalizat!',
                    'task_id' => $taskId
                ]);
            }

            // For Inertia.js requests, return a redirect back with success message
            return back()->with('success', 'Task marcat ca finalizat!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Task not found for completion', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Task not found',
                    'message' => 'Task-ul nu a fost găsit sau nu îți aparține.'
                ], 404);
            }
            return back()->withErrors(['message' => 'Task-ul nu a fost găsit sau nu îți aparține.']);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error completing task', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            $message = 'Eroare de bază de date. Încearcă din nou în câteva momente.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Database error',
                    'message' => $message
                ], 500);
            }
            return back()->withErrors(['message' => $message]);

        } catch (\Illuminate\Database\DeadlockException $e) {
            Log::warning('Deadlock detected while completing task', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            $message = 'Task-ul este în curs de procesare. Încearcă din nou în câteva secunde.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Concurrent operation',
                    'message' => $message
                ], 409);
            }
            return back()->withErrors(['message' => $message]);

        } catch (\Exception $e) {
            Log::error('Unexpected error completing task', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'A apărut o eroare neașteptată la finalizarea task-ului.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Server error',
                    'message' => $message
                ], 500);
            }
            return back()->withErrors(['message' => $message]);
        }
    }

    /**
     * Format reminder time for human reading
     */
    private function formatRemindTime(Carbon $remindAt): string
    {
        $now = now();
        
        if ($remindAt->isToday()) {
            return 'Azi, ' . $remindAt->format('H:i');
        } elseif ($remindAt->isTomorrow()) {
            return 'Mâine, ' . $remindAt->format('H:i');
        } elseif ($remindAt->diffInDays($now) <= 7) {
            return $remindAt->locale('ro')->dayName . ', ' . $remindAt->format('H:i');
        } else {
            return $remindAt->format('d.m.Y, H:i');
        }
    }
}
