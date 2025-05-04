<?php

namespace App\Providers;

use App\Repositories\IncomingMessage\Contracts\IncomingMessageRepositoryInterface;
use App\Repositories\IncomingMessage\Eloquent\EloquentIncomingMessageRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(IncomingMessageRepositoryInterface::class, EloquentIncomingMessageRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
