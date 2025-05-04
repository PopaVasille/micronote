<?php

namespace App\Repositories\IncomingMessage\Eloquent;

use App\Models\IncomingMessage;
use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EloquentIncomingMessageRepository implements IncomingMessageRepositoryInterface
{
    protected IncomingMessage $incomingMessage;

    // Când se creează clasa asta, îi dăm modelul IncomingMessage (unealta Eloquent)
    public function __construct(IncomingMessage $incomingMessage)
    {
        $this->incomingMessage = $incomingMessage;
    }

    /**
     * @param  array  $data
     * @return IncomingMessage
     */
    public function create(array $data): IncomingMessage
    {
        Log::info('trec prin repository');
        return $this->incomingMessage->create($data);
    }

    // Dacă aveai promisiunea findById, aici scriai codul care o îndeplinește:
    // public function findById(int $id): ?IncomingMessage
    // {
    //     return $this->model->find($id); // Folosesc metoda find a modelului Eloquent
    // }
}
