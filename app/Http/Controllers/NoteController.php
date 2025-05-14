<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\MessageClassificationService;
use Illuminate\Http\Request;
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
        $notes = $this->noteRepository->getAllByUserId($user->id);
        Log::info('$notes: '. $notes);

        // Pentru filtrare opțională după tip
        $filter = $request->query('filter');
        if ($filter) {
            $filteredNotes = $this->noteRepository->getByUserIdAndType($user->id, $filter);
            $notes = $filteredNotes;
        }

        return response()->json([
            'notes' => $notes,
            'filter' => $filter
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
            return response()->json([
                'status' => 'error',
                'message' => 'Ai atins limita de notițe pentru contul free. Actualizează la planul Plus pentru notițe nelimitate.'
            ], 403); // 403 Forbidden
        }

        if (!isset($validated['note_type']) || $validated['note_type'] === 'simple') {
            $classificationService = app(MessageClassificationService::class);
            $validated['note_type'] = $classificationService->classifyMessage($validated['content']);
        }

        // Creăm notița
        $note = $this->noteRepository->create($validated);

        // Incrementăm contorul de notițe al utilizatorului
        $user->increment('notes_count');

        // Returnăm răspuns JSON în loc de redirect
        return response()->json([
            'status' => 'success',
            'message' => 'Notiță creată cu succes',
            'data' => $note,
            'notes_count' => $user->notes_count,
            'notes_limit' => $user->monthly_notes_limit
        ], 201); // 201 Created - status code specific pentru resurse create
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
