<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(BrowserAuthServiceProvider::class);
        $this->app->bind(\App\Modules\Identity\Application\RegistrationStore::class, \App\Modules\Identity\Infrastructure\Persistence\EloquentRegistrationStore::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
