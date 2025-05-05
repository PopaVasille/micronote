<?php

namespace App\Providers;

use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use App\Repositories\IncomingMessage\Eloquent\EloquentIncomingMessageRepository;
use App\Repositories\Note\Contracts\NoteRepositoryInterface;
use App\Repositories\Note\Eloquent\EloquentNoteRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(IncomingMessageRepositoryInterface::class, EloquentIncomingMessageRepository::class);
        $this->app->bind(NoteRepositoryInterface::class, EloquentNoteRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
