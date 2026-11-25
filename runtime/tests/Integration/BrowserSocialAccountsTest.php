<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\SocialAccount;
use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserSocialAccountsTest extends BrowserTestCase
{
    private const string PATH = '/api/v1/users/me/social-accounts';

    public function testPrivateCollectionIsPaginatedAndHidesProviderIdentifiers(): void
    {
        $this->browser('GET', self::PATH)->assertUnauthorized();
        $user = $this->signIn();
        $this->browser('GET', self::PATH)->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);

        $first = SocialAccount::factory()->forUser($user->id)->create();
        SocialAccount::factory()->count(21)->forUser($user->id)->create();
        SocialAccount::factory()->forUser(User::factory()->create()->id)->create();

        $response = $this->browser('GET', self::PATH)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('meta.total', 22);
        $response->assertJsonMissingPath('data.0.provider_user_id');
        self::assertSame(['id', 'provider', 'linked_at'], array_keys($response->json('data.0')));

        $this->browser('GET', self::PATH.'?page=2')->assertOk()->assertJsonCount(2, 'data');
        $this->browser('GET', self::PATH.'?page=3')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 22);
        foreach (['page=0', 'page=1000001', 'per_page=101', 'user_id=2'] as $query) {
            $this->browser('GET', self::PATH.'?'.$query)->assertUnprocessable();
        }
    }

    public function testRemovalEnforcesBrowserGuardsAndIsIdempotent(): void
    {
        $user = $this->signIn();
        $link = SocialAccount::factory()->forUser($user->id)->create();
        $foreign = SocialAccount::factory()->forUser(User::factory()->create()->id)->create();
        $path = self::PATH.'/'.$link->id;

        $this->browser('DELETE', $path, csrf: false)->assertStatus(419);
        $this->browser('DELETE', $path, ['force' => true])->assertUnprocessable();
        $this->browser('DELETE', $path.'?force=1')->assertBadRequest();
        Date::setTestNow(Date::now()->addMinutes(16));
        try {
            $this->browser('DELETE', $path)->assertStatus(423);
        } finally {
            Date::setTestNow();
        }

        self::assertNotNull($link->fresh());
        $this->browser('DELETE', self::PATH.'/'.$foreign->id)->assertNoContent();
        self::assertNotNull($foreign->fresh());

        $this->browser('DELETE', $path)->assertNoContent()->assertHeader('Cache-Control', 'no-store, private');
        $this->browser('DELETE', $path)->assertNoContent();
        $this->browser('DELETE', self::PATH.'/9223372036854775808')->assertNotFound();
    }

    public function testLastLoginMethodReturnsConflictAfterCredentialChanges(): void
    {
        $user = $this->signIn();
        $link = SocialAccount::factory()->forUser($user->id)->create();
        $this->interleave('identity.social.destroy', static function (Request $request): void {
            User::findOrFail($request->user()->getAuthIdentifier())->forceFill(['password' => null])->save();
        });

        $this->browser('DELETE', self::PATH.'/'.$link->id)
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json');
        self::assertNotNull($link->fresh());
    }

    public function testEmptyBodyIsAcceptedAndMalformedJsonLeavesTheLinkInPlace(): void
    {
        $user = $this->signIn();
        $link = SocialAccount::factory()->forUser($user->id)->create();
        $url = 'https://naturally.test'.self::PATH.'/'.$link->id;
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ORIGIN' => 'https://naturally.test',
            'HTTP_X_XSRF_TOKEN' => urldecode($this->browserCookies['XSRF-TOKEN']),
            'REMOTE_ADDR' => $this->browserIp,
        ];

        $this->call('DELETE', $url, [], $this->browserCookies, [], $headers, '{')
            ->assertBadRequest()
            ->assertJsonPath('code', 'invalid_request');
        self::assertNotNull($link->fresh());
        $this->call('DELETE', $url, [], $this->browserCookies, [], $headers, '')->assertNoContent();
        self::assertNull($link->fresh());
    }

    #[DataProvider('socialRoutes')]
    public function testSessionRevokedDuringRequestCannotAccessSocialLinks(string $method, string $route): void
    {
        $user = $this->signIn();
        $link = SocialAccount::factory()->forUser($user->id)->create();
        $this->interleave($route, static function (Request $request): void {
            DB::table('sessions')->where('id', $request->session()->getId())->delete();
        });

        $path = $method === 'DELETE' ? self::PATH.'/'.$link->id : self::PATH;
        $this->browser($method, $path)->assertUnauthorized();
        self::assertNotNull($link->fresh());
    }

    public static function socialRoutes(): iterable
    {
        yield 'listing' => ['GET', 'identity.social.index'];
        yield 'removal' => ['DELETE', 'identity.social.destroy'];
    }

    public function testUnlinkBudgetAppliesToUnknownIdsAndFailsClosed(): void
    {
        $user = $this->signIn();
        for ($i = 1; $i <= 10; $i++) {
            $this->browser('DELETE', self::PATH.'/999999', server: ['REMOTE_ADDR' => '2001:db8::'.dechex($i)])
                ->assertNoContent();
        }
        $this->browser('DELETE', self::PATH.'/999999', server: ['REMOTE_ADDR' => '2001:db8::ff'])->assertStatus(429);

        $this->app->make('config')->set('database.redis.outage', [
            'host' => 'redis', 'port' => 1, 'database' => 1, 'timeout' => 0.2, 'read_timeout' => 0.2,
        ]);
        $this->app->make('config')->set('cache.stores.outage', ['driver' => 'redis', 'connection' => 'outage']);
        $this->app->forgetInstance('redis');
        $original = $this->app->make(RateLimiter::class);
        $unavailable = new RateLimiter($this->app->make('cache')->store('outage'));
        $unavailable->for('identity-social-change', $original->limiter('identity-social-change'));
        $this->app->instance(RateLimiter::class, $unavailable);

        $link = SocialAccount::factory()->forUser($user->id)->create();
        $this->browser('DELETE', self::PATH.'/'.$link->id)->assertStatus(503);
        self::assertNotNull($link->fresh());
    }

    private function signIn(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ])->assertNoContent();

        return $user;
    }

    private function interleave(string $route, \Closure $change): void
    {
        $this->app->instance('test.social-interleaving', new class($change)
        {
            public function __construct(private readonly \Closure $change) {}

            public function handle(Request $request, \Closure $next): Response
            {
                ($this->change)($request);

                return $next($request);
            }
        });
        $this->app->make('router')->getRoutes()->getByName($route)->middleware('test.social-interleaving');
    }
}
