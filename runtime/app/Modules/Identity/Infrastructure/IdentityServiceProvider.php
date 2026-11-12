<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(BrowserAuthServiceProvider::class);
        $this->app->bind(\App\Modules\Identity\Application\RecoveryDelivery::class, RecoveryLinkDelivery::class);
        $this->app->bind(\App\Modules\Identity\Application\EmailVerificationStore::class, \App\Modules\Identity\Infrastructure\Persistence\MysqlEmailVerificationStore::class);
        $this->app->bind(\App\Modules\Identity\Application\VerificationDelivery::class, EmailVerificationDelivery::class);
        $this->app->bind(\App\Modules\Identity\Application\PasswordMutation::class, \App\Modules\Identity\Infrastructure\Persistence\MysqlPasswordMutation::class);
        $this->app->bind(\App\Modules\Identity\Application\SessionRevocation::class, \App\Modules\Identity\Infrastructure\Persistence\MysqlSessionRevocation::class);
        $this->app->bind(\App\Modules\Identity\Application\AccountReader::class, \App\Modules\Identity\Infrastructure\Persistence\EloquentAccountReader::class);
        $this->app->bind(\App\Modules\Identity\Application\RegistrationStore::class, \App\Modules\Identity\Infrastructure\Persistence\EloquentRegistrationStore::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
