<?php


namespace App\Repositories\Note\Contracts;

use App\Models\Note;
use Illuminate\Database\Eloquent\Collection;

interface NoteRepositoryInterface
{
    /**
     * @param  int  $userId
     * @param  string|null  $searchQuery
     * @return Collection
     */
    public function getAllByUserId(int $userId, ?string $searchQuery = null): Collection;

    /**
     * @param  int  $userId
     * @param  string  $noteType
     * @param  string|null  $searchQuery
     * @return Collection
     */
    public function getByUserIdAndType(int $userId, string $noteType, ?string $searchQuery = null): Collection;

    /**
     * @param  int  $userId
     * @param  string|null  $searchQuery
     * @return Collection
     */
    public function getFavoriteByUserId(int $userId, ?string $searchQuery = null): Collection;

    /**
     * @param  int  $userId
     * @param  string|null  $searchQuery
     * @return Collection
     */
    public function getCompletedByUserId(int $userId, ?string $searchQuery = null): Collection;

    /**
     * @param  array  $data
     * @return Note
     */
    public function create(array $data): Note;

    /**
     * @param  Note  $note
     * @param  array  $data
     * @return Note
     */
    public function update(Note $note, array $data): Note;
}
