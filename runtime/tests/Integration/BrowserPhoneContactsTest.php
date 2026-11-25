<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\Phone;
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
final class BrowserPhoneContactsTest extends BrowserTestCase
{
    private const string PATH = '/api/v1/users/me/phones';

    private function actor(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    private function contact(array $overrides = []): array
    {
        return array_replace(['label' => 'Mobile', 'country_code' => 'BR', 'number' => '+5511999990001', 'main' => true], $overrides);
    }

    public function testCollectionRoundTripUsesSessionOwnerAndMaintainsPrimaryOnIdempotentRemoval(): void
    {
        $user = $this->actor();
        $foreign = Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create();
        $this->browser('GET', self::PATH)->assertOk()->assertExactJson(['data' => []])->assertHeader('Cache-Control', 'no-store, private');
        $first = $this->browser('POST', self::PATH, $this->contact(['label' => ' Mobile ', 'country_code' => 'br']))->assertCreated();
        $firstId = $first->json('data.id');
        $first->assertExactJson(['data' => ['id' => $firstId, 'label' => 'Mobile', 'country_code' => 'BR', 'number' => '+5511999990001', 'main' => true]])->assertHeader('Cache-Control', 'no-store, private');
        self::assertSame($user->id, Phone::findOrFail($firstId)->user_id);
        $second = $this->browser('POST', self::PATH, $this->contact(['label' => 'Office', 'number' => '+5511999990002', 'main' => false]))->assertCreated();
        $secondId = $second->json('data.id');
        $this->browser('POST', self::PATH, $this->contact(['number' => '+5511999990003', 'main' => false]))->assertUnprocessable();
        $this->browser('GET', self::PATH)->assertJsonCount(2, 'data')->assertJsonPath('data.1.main', false);
        $this->browser('DELETE', self::PATH.'/'.$foreign->id)->assertNoContent()->assertContent('');
        self::assertNotNull($foreign->fresh());
        $this->browser('DELETE', self::PATH.'/'.$firstId)->assertNoContent()->assertContent('');
        $this->browser('DELETE', self::PATH.'/'.$firstId)->assertNoContent();
        $this->browser('GET', self::PATH)->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $secondId)->assertJsonPath('data.0.main', true);
        $this->browser('DELETE', self::PATH.'/'.$secondId)->assertNoContent();
        $this->browser('GET', self::PATH)->assertExactJson(['data' => []]);
    }

    public function testSessionAndRealCsrfProtectBothMutationMethods(): void
    {
        $this->bootstrapBrowser();
        $this->browser('GET', self::PATH)->assertUnauthorized();
        $this->browser('POST', self::PATH, $this->contact())->assertUnauthorized();
        $this->browser('DELETE', self::PATH.'/1')->assertUnauthorized();
        $user = $this->actor();
        $this->browser('POST', self::PATH, $this->contact(), csrf: false)->assertStatus(419);
        $this->browser('DELETE', self::PATH.'/1', csrf: false)->assertStatus(419);
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
    }

    public function testStrictInputAndBusinessFailuresHaveSanitizedProblemDetails(): void
    {
        $user = $this->actor();
        foreach ([[], $this->contact(['user_id' => 999]), $this->contact(['main' => '1']), $this->contact(['main' => 1]), $this->contact(['country_code' => 'GB']), $this->contact(['number' => '+551132345678']), $this->contact(['label' => "Work\nPhone"]), $this->contact(['main' => false])] as $input) {
            $this->browser('POST', self::PATH, $input)->assertUnprocessable()->assertHeader('Content-Type', 'application/problem+json')->assertJsonPath('code', 'validation_failed');
        }
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
        $foreign = Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create(['number' => '+5511999990001']);
        $response = $this->browser('POST', self::PATH, $this->contact())->assertUnprocessable();
        self::assertSame(['contact' => ['The field is invalid.']], $response->json('errors'));
        self::assertStringNotContainsString($foreign->number->value, $response->getContent());
    }

    public function testTransportAndIdentifierBoundsCannotSelectAnotherResource(): void
    {
        $user = $this->actor();
        $phone = Phone::factory()->forUser($user->id)->asMain()->create();
        $this->browser('GET', self::PATH.'?user_id=999')->assertStatus(400);
        $this->browser('POST', self::PATH.'?user_id=999', $this->contact())->assertStatus(400);
        $this->browser('DELETE', self::PATH.'/'.$phone->id.'?force=true')->assertStatus(400);
        $this->browser('POST', self::PATH, $this->contact(), server: ['CONTENT_TYPE' => 'text/plain'])->assertStatus(415);
        $this->browser('POST', self::PATH, $this->contact(), server: ['HTTP_ORIGIN' => 'https://hostile.test'])->assertForbidden();
        $this->browser('DELETE', self::PATH.'/'.$phone->id, ['force' => true])->assertUnprocessable();
        foreach (['0', '-1', '001', 'abc', '9223372036854775808', str_repeat('9', 40)] as $id) {
            $this->browser('DELETE', self::PATH.'/'.$id)->assertNotFound();
        }
        self::assertNotNull($phone->fresh());
    }

    public function testReadsRemainAvailableWhenRecentAuthenticationForWritesExpires(): void
    {
        $user = $this->actor();
        Date::setTestNow(Date::now()->addMinutes(16));
        try {
            $this->browser('GET', self::PATH)->assertOk();
            $this->browser('POST', self::PATH, $this->contact())->assertStatus(423);
            $this->browser('DELETE', self::PATH.'/1')->assertStatus(423);
        } finally { Date::setTestNow(); }
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
    }

    public function testMutationBudgetIsSharedAcrossMethodsAndClientIpChanges(): void
    {
        $user = $this->actor();
        for ($i = 0; $i < 10; $i++) {
            $this->browserIp = '2001:db8:'.bin2hex(random_bytes(2)).'::1';
            $this->browser('DELETE', self::PATH.'/999999')->assertNoContent();
        }
        $this->browser('POST', self::PATH, $this->contact())->assertStatus(429)->assertHeader('Retry-After');
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
        $this->browser('GET', self::PATH)->assertOk();
    }

    public function testIpMutationBudgetCannotBeBypassedByRotatingAccounts(): void
    {
        for ($account = 0; $account < 3; $account++) {
            $this->actor();
            for ($i = 0; $i < 10; $i++) { $this->browser('DELETE', self::PATH.'/999999')->assertNoContent(); }
        }
        $user = $this->actor();
        $this->browser('POST', self::PATH, $this->contact())->assertStatus(429);
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
    }

    public function testRedisLimiterOutageBlocksWritesWithoutModifyingContacts(): void
    {
        $user = $this->actor();
        $this->app->make('config')->set('database.redis.outage', ['host' => 'redis', 'port' => 1, 'database' => 1, 'timeout' => 0.2, 'read_timeout' => 0.2]);
        $this->app->make('config')->set('cache.stores.outage', ['driver' => 'redis', 'connection' => 'outage']);
        $this->app->forgetInstance('redis');
        $original = $this->app->make(\Illuminate\Cache\RateLimiter::class);
        $unavailable = new \Illuminate\Cache\RateLimiter($this->app->make('cache')->store('outage'));
        $unavailable->for('identity-phone-change', $original->limiter('identity-phone-change'));
        $this->app->instance(\Illuminate\Cache\RateLimiter::class, $unavailable);
        $this->browser('POST', self::PATH, $this->contact())->assertStatus(503);
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
    }

    public function testRevocationAfterHttpAuthenticationBlocksReadsAndBothWrites(): void
    {
        $this->app->instance('test.phone.revoke', new class {
            public function handle(\Illuminate\Http\Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
            {
                \Illuminate\Support\Facades\DB::table('sessions')->where('id', $request->session()->getId())->delete();
                return $next($request);
            }
        });
        foreach ([['identity.phones.index', 'GET', self::PATH, []], ['identity.phones.store', 'POST', self::PATH, $this->contact()], ['identity.phones.destroy', 'DELETE', self::PATH.'/1', []]] as [$name, $method, $path, $input]) {
            $user = $this->actor();
            $this->app->make('router')->getRoutes()->getByName($name)->middleware('test.phone.revoke');
            $this->browser($method, $path, $input)->assertUnauthorized();
            self::assertSame(0, Phone::where('user_id', $user->id)->count());
            self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
            $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        }
    }

    public function testMalformedOrScalarJsonCannotDeleteAContact(): void
    {
        $user = $this->actor();
        $phone = Phone::factory()->forUser($user->id)->asMain()->create();
        foreach ([['{', 400], ['null', 422], ['true', 422], ['"text"', 422]] as [$content, $status]) {
            $this->call('DELETE', 'https://naturally.test'.self::PATH.'/'.$phone->id, [], $this->browserCookies, [], [
                'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
                'HTTP_ORIGIN' => 'https://naturally.test', 'REMOTE_ADDR' => $this->browserIp,
                'HTTP_X_XSRF_TOKEN' => urldecode($this->browserCookies['XSRF-TOKEN']),
            ], $content)->assertStatus($status)->assertJsonPath('code', $status === 400 ? 'invalid_request' : 'validation_failed');
            self::assertNotNull($phone->fresh());
        }
    }
}
