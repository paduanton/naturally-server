<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserCredentialRevisionTest extends BrowserTestCase
{
    public function testLoginValidatedBeforeCredentialMutationCannotCreateAUsableStaleSession(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        Event::listen(Validated::class, function (Validated $event): void {
            // Deterministic interleaving: a different request commits credential replacement
            // after the old password is validated and before this login stores its session.
            User::findOrFail($event->user->getAuthIdentifier())->forceFill(['password' => bin2hex(random_bytes(24))])->save();
            $this->database->connection()->table('sessions')->where('user_id', $event->user->getAuthIdentifier())->delete();
        });
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testCredentialRemovalInvalidatesPreviouslyAuthenticatedCookies(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        $user->forceFill(['password' => null])->save();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testCredentialRemovedAfterAuthorizationCannotBeReplacedImplicitly(): void
    {
        $user = $this->authenticated();
        $this->interleave('identity.password.update', removeCredential: true);
        $next = bin2hex(random_bytes(24));
        $this->browser('PUT', '/api/v1/auth/password', ['password' => $next, 'password_confirmation' => $next])->assertUnprocessable();
        self::assertNull($user->fresh()->password);
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testSessionRevokedAfterAuthorizationCannotChangePassword(): void
    {
        $user = $this->authenticated();
        $before = $user->password;
        $this->interleave('identity.password.update');
        $next = bin2hex(random_bytes(24));
        $this->browser('PUT', '/api/v1/auth/password', ['password' => $next, 'password_confirmation' => $next])->assertUnauthorized();
        self::assertTrue(hash_equals($before, $user->fresh()->password));
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
    }

    public function testSessionRevokedAfterAuthorizationCannotRevokeOtherSessions(): void
    {
        $user = $this->authenticated();
        $this->interleave('identity.sessions.others');
        $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertUnauthorized();
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
    }

    private function authenticated(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    private function interleave(string $route, bool $removeCredential = false): void
    {
        $this->app->instance('test.interleaving', new class($removeCredential) {
            public function __construct(private readonly bool $removeCredential) {}

            public function handle(\Illuminate\Http\Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
            {
                if ($this->removeCredential) {
                    User::findOrFail($request->user()->getAuthIdentifier())->forceFill(['password' => null])->save();
                } else {
                    \Illuminate\Support\Facades\DB::table('sessions')->where('id', $request->session()->getId())->delete();
                }
                return $next($request);
            }
        });
        $this->app->make('router')->getRoutes()->getByName($route)->middleware('test.interleaving');
    }
}
