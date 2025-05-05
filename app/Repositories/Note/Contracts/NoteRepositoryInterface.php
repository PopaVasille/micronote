<?php


namespace App\Repositories\Note\Contracts;

use App\Models\Note;
use Illuminate\Database\Eloquent\Collection;

interface NoteRepositoryInterface
{
    /**
     * @param  int  $userId
     * @return Collection
     */
    public function getAllByUserId(int $userId): Collection;

    /**
     * @param  int  $userId
     * @param  string  $noteType
     * @return Collection
     */
    public function getByUserIdAndType(int $userId, string $noteType): Collection;

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
