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
        $this->app->bind(\App\Modules\Identity\Http\CreateUser::class, fn ($app) => new \App\Modules\Identity\Http\CreateUser($app->make(\App\Modules\Identity\Application\RegisterAccount::class), $app->make('auth')->createUserProvider('users')));
        $this->app->singleton(\Laravel\Fortify\Contracts\RegisterResponse::class, \App\Modules\Identity\Http\RegisteredResponse::class);
        $this->app->bind(\Laravel\Fortify\Http\Requests\LoginRequest::class, \App\Modules\Identity\Http\BoundedLoginRequest::class);
        $this->app->bind(\Laravel\Fortify\LoginRateLimiter::class, OpaqueLoginRateLimiter::class);
        $this->app->singleton(\Laravel\Fortify\Contracts\LoginResponse::class, \App\Modules\Identity\Http\SessionResponse::class);
    }

    public function boot(): void
    {
        Sanctum::getAccessTokenFromRequestUsing(static fn (): ?string => null);
        Fortify::createUsersUsing(\App\Modules\Identity\Http\CreateUser::class);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, StampAuthentication::class);
        $this->app->afterResolving(\Illuminate\Cache\RateLimiter::class, static function (\Illuminate\Cache\RateLimiter $limiter): void {
            $limiter->for('identity-login', static fn (\Illuminate\Http\Request $request) => [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(hash_hmac('sha256', 'account|'.strtolower(trim(is_string($request->input('email')) ? $request->input('email') : '')).'|'.$request->ip(), config('app.key'))),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by(hash_hmac('sha256', 'ip|'.$request->ip(), config('app.key'))),
            ]);
        });
        $this->app->make('router')->aliasMiddleware('identity.throttle', \App\Modules\Identity\Http\Middleware\FailClosedThrottle::class);
        $this->app->afterResolving(\Illuminate\Cache\RateLimiter::class, static function (\Illuminate\Cache\RateLimiter $limiter): void {
            $limiter->for('identity-registration', static fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by(hash_hmac('sha256', (string) $request->ip(), config('app.key'))));
        });
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
