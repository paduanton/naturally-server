<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserLoginTest extends BrowserTestCase
{
    public function testLoginRotatesGuestSessionAndReturnsNoToken(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $guestId = $this->database->connection()->table('sessions')->value('id');
        $this->browser('POST', '/api/v1/auth/login', ['email' => strtoupper($user->email), 'password' => $password])->assertNoContent();
        self::assertNull($this->database->connection()->table('sessions')->find($guestId));
        self::assertSame($user->id, $this->database->connection()->table('sessions')->where('user_id', $user->id)->value('user_id'));
        Route::get('/api/v1/auth/who', static fn (\Illuminate\Http\Request $request) => response()->json(['id' => $request->user()->getAuthIdentifier()]))->middleware(['identity.browser', 'auth:sanctum']);
        $this->browser('GET', '/api/v1/auth/who')->assertOk()->assertJsonPath('id', $user->id);
    }

    public function testUnknownWrongAndSocialOnlyCredentialsShareTheSameFailure(): void
    {
        $user = User::factory()->create();
        $social = User::factory()->socialOnly()->create();
        $this->bootstrapBrowser();
        $errors = null;
        foreach ([$user->email, $social->email, bin2hex(random_bytes(8)).'@example.test'] as $email) {
            $response = $this->browser('POST', '/api/v1/auth/login', ['email' => $email, 'password' => bin2hex(random_bytes(24))])->assertUnprocessable();
            $errors ??= $response->json('errors');
            self::assertSame($errors, $response->json('errors'));
        }
        self::assertSame(0, $this->database->connection()->table('sessions')->whereNotNull('user_id')->count());
    }

    public function testRememberMeDeletedUsersAndMissingCsrfCannotAuthenticate(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password], csrf: false)->assertStatus(419);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password, 'remember' => true])->assertUnprocessable();
        $user->delete();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertUnprocessable();
    }

    public function testAccountAndIpLimiterBlocksRepeatedAttemptsWithRetryAfter(): void
    {
        $email = bin2hex(random_bytes(8)).'@example.test';
        $this->bootstrapBrowser();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->browser('POST', '/api/v1/auth/login', ['email' => $email, 'password' => bin2hex(random_bytes(24))])->assertUnprocessable();
        }
        $this->browser('POST', '/api/v1/auth/login', ['email' => $email, 'password' => bin2hex(random_bytes(24))])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function testIpBudgetCannotBeBypassedByRotatingAccountNames(): void
    {
        $this->bootstrapBrowser();
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->browser('POST', '/api/v1/auth/login', ['email' => bin2hex(random_bytes(8)).'@example.test', 'password' => bin2hex(random_bytes(24))])->assertUnprocessable();
        }
        $this->browser('POST', '/api/v1/auth/login', ['email' => bin2hex(random_bytes(8)).'@example.test', 'password' => bin2hex(random_bytes(24))])->assertStatus(429);
    }

    public function testRealRedisConnectionFailureDoesNotAuthenticate(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->app->make('config')->set('database.redis.outage', ['host' => 'redis', 'port' => 1, 'database' => 1, 'timeout' => 0.2, 'read_timeout' => 0.2]);
        $this->app->make('config')->set('cache.stores.outage', ['driver' => 'redis', 'connection' => 'outage']);
        $this->app->forgetInstance('redis');
        $original = $this->app->make(\Illuminate\Cache\RateLimiter::class);
        $unavailable = new \Illuminate\Cache\RateLimiter($this->app->make('cache')->store('outage'));
        $unavailable->for('identity-login', $original->limiter('identity-login'));
        $this->app->instance(\Illuminate\Cache\RateLimiter::class, $unavailable);
        \Illuminate\Support\Facades\RateLimiter::clearResolvedInstance(\Illuminate\Cache\RateLimiter::class);
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertStatus(503);
        self::assertSame(0, $this->database->connection()->table('sessions')->whereNotNull('user_id')->count());
    }

    public function testMalformedCredentialShapesAreValidationFailures(): void
    {
        $this->bootstrapBrowser();
        foreach ([['email' => ['nested'], 'password' => 'invalid'], ['email' => 'user@example.test', 'password' => ['nested']], ['email' => 'user@example.test', 'password' => str_repeat('x', 1025)]] as $input) {
            $this->browser('POST', '/api/v1/auth/login', $input)->assertUnprocessable();
        }
    }
}
