<?php

namespace App\Repositories\IncomingMessage\Contracts;

use App\Models\IncomingMessage;


interface IncomingMessageRepositoryInterface
{
    /**
     * @param  array  $data
     * @return IncomingMessage
     */
    public function create(array $data): IncomingMessage;

    // Dacă ai avea nevoie să găsești un mesaj, ai promite și asta:
    // public function findById(int $id): ?IncomingMessage;
}
