<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\MessageClassificationService;
use Illuminate\Http\Request;
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

        $notes = match($filter) {
            'all' => $this->noteRepository->getAllByUserId($user->id),
            default => $this->noteRepository->getByUserIdAndType($user->id, $filter)
        };

        Log::info('$user: '.$user);
        Log::info('$notes: '.$notes);

        return Inertia::render('Dashboard', [
            'notes' => $notes,
            'filter' => $filter
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
    public function update(Request $request, Note $note)
    {
        // Verificăm că utilizatorul este proprietarul notiței
        $this->authorize('update', $note);

        $validated = $request->validate([
            'is_completed' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
        ]);

        // Actualizăm notița
        $updatedNote = $this->noteRepository->update($note, $validated);

        // Returnăm răspuns JSON în loc de redirect
        return response()->json([
            'status' => 'success',
            'message' => 'Notiță actualizată cu succes',
            'data' => $updatedNote
        ]);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Note $note)
    {
        //
    }
}
