<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    NotificationChannels\WhatsApp\WhatsAppServiceProvider::class,
];
