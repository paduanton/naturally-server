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
final class BrowserRevocationTest extends BrowserTestCase
{
    private function login(User $user, string $password): void
    {
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
    }

    public function testLogoutDeletesAuthenticatedStorageAndPreventsCookieReplay(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $oldCookies = $this->browserCookies;
        $oldId = $this->app->make('session')->driver()->getId();
        $this->browser('POST', '/api/v1/auth/logout')->assertNoContent();
        self::assertNull($this->database->connection()->table('sessions')->find($oldId));
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        $this->browserCookies = $oldCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testLogoutWithoutCsrfDoesNotEndTheCurrentSession(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $this->browser('POST', '/api/v1/auth/logout', csrf: false)->assertStatus(419);
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
    }

    public function testRevocationDeletesOnlyOtherSessionsOfTheCurrentActor(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $other = User::factory()->create(['password' => $password]);
        $this->login($other, $password);
        $otherCookies = $this->browserCookies;
        $this->login($user, $password);
        $oldCookies = $this->browserCookies;
        $this->login($user, $password);
        $currentCookies = $this->browserCookies;
        $this->browser('DELETE', '/api/v1/auth/sessions/others', ['user_id' => $other->id])->assertNoContent();
        $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertNoContent();
        self::assertSame(1, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        self::assertSame(1, $this->database->connection()->table('sessions')->where('user_id', $other->id)->count());
        $this->browserCookies = $oldCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        $this->browserCookies = $otherCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $other->id);
        $this->browserCookies = $currentCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function testRevocationRequiresCsrfAndRecentAuthentication(): void
    {
        $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertStatus(419);
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $this->browser('DELETE', '/api/v1/auth/sessions/others', csrf: false)->assertStatus(419);
        Date::setTestNow(Date::now()->addMinutes(15));
        try { $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertStatus(423); }
        finally { Date::setTestNow(); }
    }

    public function testAnAlreadyRevokedSessionCannotRevokeRemainingSessions(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $activeId = $this->app->make('session')->driver()->getId();
        try {
            $this->app->make(\App\Modules\Identity\Application\SessionRevocation::class)->revokeOthers($user->id, bin2hex(random_bytes(20)));
            self::fail('Inactive session accepted.');
        } catch (\App\Modules\Identity\Application\InactiveSession) {
            self::assertNotNull($this->database->connection()->table('sessions')->find($activeId));
        }
    }

    public function testMissingAndFutureConfirmationTimestampsCannotAuthorizeRevocation(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        foreach ([null, Date::now()->addMinute()->getTimestamp()] as $stamp) {
            $session = $this->app->make('session')->driver();
            $session->put('auth.password_confirmed_at', $stamp);
            $session->save();
            $this->browser('DELETE', '/api/v1/auth/sessions/others')->assertStatus(423)->assertJsonPath('code', 'reauthentication_required');
        }
    }
}
