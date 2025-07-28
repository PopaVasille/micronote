<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Http\Requests\UpdateNoteRequest;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Services\Classification\MessageClassificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
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
        $searchQuery = $request->query('search');

        $notes = match($filter) {
            'all' => $this->noteRepository->getAllByUserId($user->id,$searchQuery),
            'favorite' => $this->noteRepository->getFavoriteByUserId($user->id,$searchQuery),
            'completed' => $this->noteRepository->getCompletedByUserId($user->id,$searchQuery),
            default => $this->noteRepository->getByUserIdAndType($user->id, $filter)
        };

        Log::info('$user: '.$user);
        Log::info('$notes: '.$notes);

        return Inertia::render('Dashboard', [
            'notes' => $notes,
            'filter' => $filter,
            'search' => $searchQuery,
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
        //
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
}
