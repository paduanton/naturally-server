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
final class BrowserSessionTest extends BrowserTestCase
{
    private function login(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    public function testCurrentAccountUsesSessionActorAndAllowlistedFields(): void
    {
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        $user = $this->login();
        $other = User::factory()->create();
        $this->browser('GET', '/api/v1/auth/me?user_id='.$other->id)->assertOk()->assertExactJson(['data' => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'email_verified' => false]]);
    }

    public function testIdleTimeoutAndAbsoluteTimeoutAreIndependent(): void
    {
        $this->login();
        Date::setTestNow(Date::now()->addMinutes(121));
        try { $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized(); }
        finally { Date::setTestNow(); }
    }

    public function testActiveSessionExpiresAtTwelveHoursDespiteFreshDatabaseActivity(): void
    {
        $user = $this->login();
        $sessionId = $this->app->make('session')->driver()->getId();
        Date::setTestNow(Date::now()->addHours(12));
        try {
            $this->database->connection()->table('sessions')->where('user_id', $user->id)->update(['last_activity' => Date::now()->getTimestamp()]);
            $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
            self::assertNull($this->database->connection()->table('sessions')->find($sessionId));
        } finally { Date::setTestNow(); }
    }

    public function testMissingAbsoluteTimestampFailsClosed(): void
    {
        $this->login();
        $session = $this->app->make('session')->driver();
        $session->forget('_identity_issued_at');
        $session->save();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testDeletedAccountCannotReuseAnAuthenticatedCookie(): void
    {
        $user = $this->login();
        $user->delete();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testFutureTimestampFailsClosed(): void
    {
        $this->login();
        $session = $this->app->make('session')->driver();
        $session->put('_identity_issued_at', Date::now()->addMinute()->getTimestamp());
        $session->save();
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testEncryptedBrowserCookieWorksOnAnotherApplicationReplica(): void
    {
        $user = $this->login();
        $replica = \App\Shared\Infrastructure\RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        $replica->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $this->database->configureReplica($replica);
        try {
            $request = \Illuminate\Http\Request::create('https://naturally.test/api/v1/auth/me', 'GET', [], $this->browserCookies, [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_ORIGIN' => 'https://naturally.test']);
            $kernel = $replica->make(\Illuminate\Contracts\Http\Kernel::class);
            $response = $kernel->handle($request);
            \Illuminate\Testing\TestResponse::fromBaseResponse($response)->assertOk()->assertJsonPath('data.id', $user->id);
            $kernel->terminate($request, $response);
        } finally {
            $replica->make('db')->disconnect();
            $replica->flush();
            \Illuminate\Container\Container::setInstance($this->app);
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            \Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
            \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app->make('db'));
        }
    }
}
