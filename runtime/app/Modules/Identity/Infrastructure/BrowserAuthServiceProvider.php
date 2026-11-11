<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use App\Modules\Identity\Application\RegisterAccount;
use App\Modules\Identity\Http as IdentityHttp;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts as FortifyResponses;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;

final class BrowserAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->register(\Laravel\Fortify\FortifyServiceProvider::class);
        $this->app->register(\Laravel\Sanctum\SanctumServiceProvider::class);
        $this->app->bind(IdentityHttp\CreateUser::class, fn ($app) => new IdentityHttp\CreateUser($app->make(RegisterAccount::class), $app->make('auth')->createUserProvider('users')));
        $this->app->bind(\Laravel\Fortify\Http\Requests\LoginRequest::class, IdentityHttp\BoundedLoginRequest::class);
        $this->app->bind(\Laravel\Fortify\LoginRateLimiter::class, OpaqueLoginRateLimiter::class);
        $this->app->singleton(FortifyResponses\RegisterResponse::class, IdentityHttp\RegisteredResponse::class);
        $this->app->singleton(FortifyResponses\LoginResponse::class, IdentityHttp\AuthenticatedResponse::class);
        $this->app->singleton(FortifyResponses\LogoutResponse::class, IdentityHttp\SessionResponse::class);
        $this->app->singleton(FortifyResponses\PasswordConfirmedResponse::class, IdentityHttp\SessionResponse::class);
        $this->app->singleton(FortifyResponses\PasswordUpdateResponse::class, IdentityHttp\PasswordChangedResponse::class);
    }

    public function boot(): void
    {
        Sanctum::getAccessTokenFromRequestUsing(static fn (): ?string => null);
        Fortify::createUsersUsing(IdentityHttp\CreateUser::class);
        Fortify::updateUserPasswordsUsing(IdentityHttp\UpdatePassword::class);
        \Illuminate\Auth\Notifications\VerifyEmail::createUrlUsing(static fn ($user): string => \Illuminate\Support\Facades\URL::temporarySignedRoute('identity.email.verify', \Illuminate\Support\Facades\Date::now()->addMinutes(60), ['id' => $user->getAuthIdentifier(), 'hash' => sha1($user->getEmailForVerification())]));
        Event::listen(Login::class, StampAuthentication::class);
        $this->app->afterResolving(RateLimiter::class, static function (RateLimiter $limiter): void {
            $limiter->for('identity-login', static function (Request $request): array {
                $email = $request->input('email');
                $identity = is_string($email) ? strtolower(trim($email)) : '';
                return [
                    Limit::perMinute(5)->by(hash_hmac('sha256', 'account|'.$identity.'|'.$request->ip(), config('app.key'))),
                    Limit::perMinute(30)->by(hash_hmac('sha256', 'ip|'.$request->ip(), config('app.key'))),
                ];
            });
            $limiter->for('identity-registration', static fn (Request $request) => Limit::perMinute(5)->by(hash_hmac('sha256', (string) $request->ip(), config('app.key'))));
            $limiter->for('identity-confirmation', static fn (Request $request) => Limit::perMinute(5)->by(hash_hmac('sha256', 'confirm|'.$request->user()->getAuthIdentifier().'|'.$request->ip(), config('app.key'))));
        });
        $router = $this->app->make('router');
        $router->aliasMiddleware('identity.throttle', IdentityHttp\Middleware\FailClosedThrottle::class);
        $router->middlewareGroup('identity.browser', [
            IdentityHttp\Middleware\BrowserBoundary::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            IdentityHttp\Middleware\EnforceSessionAge::class,
            \Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
            IdentityHttp\Middleware\VerifyBrowserRequest::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
    }
}
