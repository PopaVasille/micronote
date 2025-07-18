<?php

namespace App\Repositories\Note\Eloquent;

use App\Models\Note;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class EloquentNoteRepository implements NoteRepositoryInterface
{
    protected Note $note;

    /**
     * @param  Note  $note
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * @param  int  $userId
     * @return Collection
     */
    public function getAllByUserId(int $userId): Collection
    {
        return $this->note->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->with('tags')
            ->get();
    }

    public function getFavoriteByUserId(int $userId): Collection
    {
        return $this->note->where('user_id', $userId)
            ->where('is_favorite', true)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->with('tags')
            ->get();
    }
    public function getCompletedByUserId(int $userId): Collection
    {
        return $this->note->where('user_id', $userId)
            ->where('is_completed', true)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->with('tags')
            ->get();
    }

    /**
     * @param  int  $userId
     * @param  string  $noteType
     * @return Collection
     */
    public function getByUserIdAndType(int $userId, string $noteType): Collection
    {
        return $this->note->where('user_id', $userId)
            ->where('note_type', $noteType)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->with('tags')
            ->get();
    }

    /**
     * @param  array  $data
     * @return Note
     */
    public function create(array $data): Note
    {
        // Generează UUID dacă nu există
        if (!isset($data['uuid'])) {
            $data['uuid'] = (string) Str::uuid();
        }

        return $this->note->create($data);
    }

    /**
     * @param  Note  $note
     * @param  array  $data
     * @return Note
     */
    public function update(Note $note, array $data): Note
    {
        $note->update($data);
        return $note->fresh();
    }
}
