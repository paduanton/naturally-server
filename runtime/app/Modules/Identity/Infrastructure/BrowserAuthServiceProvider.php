<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Http\Middleware\BrowserBoundary;
use App\Modules\Identity\Http\Middleware\VerifyBrowserRequest;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;

final class BrowserAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->register(\Laravel\Fortify\FortifyServiceProvider::class);
        $this->app->register(\Laravel\Sanctum\SanctumServiceProvider::class);
    }

    public function boot(): void
    {
        Sanctum::getAccessTokenFromRequestUsing(static fn (): ?string => null);
        $this->app->make('router')->middlewareGroup('identity.browser', [
            BrowserBoundary::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            VerifyBrowserRequest::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
    }
}
