<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserPasswordTest extends BrowserTestCase
{
    private function login(User $user, string $password): void
    {
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
    }

    public function testConfirmationRestoresRecentProofWithoutExtendingAbsoluteExpiry(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $issuedAt = $this->app->make('session')->driver()->get('_identity_issued_at');
        Date::setTestNow(Date::now()->addMinutes(16));
        try {
            $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertStatus(423);
            $this->browser('POST', '/api/v1/auth/password/confirm', ['password' => bin2hex(random_bytes(24))])->assertUnprocessable();
            $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertStatus(423);
            $this->browser('POST', '/api/v1/auth/password/confirm', ['password' => $password])->assertNoContent();
            self::assertSame($issuedAt, $this->app->make('session')->driver()->get('_identity_issued_at'));
            $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertNoContent();
        } finally { Date::setTestNow(); }
    }

    public function testConfirmationRequiresCsrfAndBoundedStringInput(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $this->browser('POST', '/api/v1/auth/password/confirm', ['password' => $password], csrf: false)->assertStatus(419);
        foreach ([[], ['password' => ['invalid']], ['password' => str_repeat('x', 1025)]] as $input) {
            $this->browser('POST', '/api/v1/auth/password/confirm', $input)->assertUnprocessable();
        }
    }

    public function testConfirmationAttemptsAreRateLimited(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        for ($i = 0; $i < 5; $i++) { $this->browser('POST', '/api/v1/auth/password/confirm', ['password' => bin2hex(random_bytes(24))])->assertUnprocessable(); }
        $this->browser('POST', '/api/v1/auth/password/confirm', ['password' => $password])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function testPasswordChangeRevokesAllOwnSessionsAndRecoveryTokens(): void
    {
        $password = bin2hex(random_bytes(24));
        $next = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $other = User::factory()->create(['password' => $password]);
        $this->login($other, $password);
        $otherCookies = $this->browserCookies;
        $this->login($user, $password);
        $oldCookies = $this->browserCookies;
        $this->login($user, $password);
        $currentCookies = $this->browserCookies;
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $this->browser('PUT', '/api/v1/auth/password', ['password' => $next, 'password_confirmation' => $next, 'user_id' => $other->id])->assertNoContent();
        self::assertTrue($this->app->make('hash')->check($next, $user->fresh()->password));
        self::assertTrue($this->app->make('hash')->check($password, $other->fresh()->password));
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        self::assertSame(0, $this->database->connection()->table('password_reset_tokens')->where('email', $user->email)->count());
        foreach ([$oldCookies, $currentCookies] as $cookies) {
            $this->browserCookies = $cookies;
            $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        }
        $this->browserCookies = $otherCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $other->id);
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertUnprocessable();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $next])->assertNoContent();
    }

    public function testStaleAuthenticationAndWeakPasswordCannotMutateCredentials(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $next = bin2hex(random_bytes(24));
        $this->browser('PUT', '/api/v1/auth/password', ['password' => $next, 'password_confirmation' => $next], csrf: false)->assertStatus(419);
        $this->browser('PUT', '/api/v1/auth/password', ['password' => str_repeat('x', 14), 'password_confirmation' => str_repeat('x', 14)])->assertUnprocessable();
        Date::setTestNow(Date::now()->addMinutes(15));
        try { $this->browser('PUT', '/api/v1/auth/password', ['password' => $next, 'password_confirmation' => $next])->assertStatus(423); }
        finally { Date::setTestNow(); }
        self::assertTrue($this->app->make('hash')->check($password, $user->fresh()->password));
    }

    public function testSocialOnlyCredentialsCannotBeConfirmedOrReplacedImplicitly(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $user->forceFill(['password' => null])->save();
        $this->browser('POST', '/api/v1/auth/password/confirm', ['password' => $password])->assertUnprocessable();
        $next = bin2hex(random_bytes(24));
        $this->browser('PUT', '/api/v1/auth/password', ['password' => $next, 'password_confirmation' => $next])->assertUnprocessable();
        self::assertNull($user->fresh()->password);
    }
}
