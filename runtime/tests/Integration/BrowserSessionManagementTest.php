<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\CurrentSessionRevocationDenied;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\RevokeSession;
use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Infrastructure\Persistence\User;
use App\Modules\Identity\Infrastructure\Persistence\SessionReferenceEncoder;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserSessionManagementTest extends BrowserTestCase
{
    private function login(User $user, string $password): SessionActor
    {
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return new SessionActor($user->id, $this->app->make('session')->driver()->getId());
    }

    private function path(string $reference): string { return '/api/v1/auth/sessions/'.$reference; }

    public function testSingleRevocationPreventsReplayPreservesCurrentAndIsIdempotent(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $other = $this->login($user, $password); $otherCookies = $this->browserCookies;
        $current = $this->login($user, $password); $currentCookies = $this->browserCookies;
        $rows = $this->browser('GET', '/api/v1/auth/sessions')->assertOk()->json('data');
        $reference = array_values(array_filter($rows, static fn (array $row): bool => !$row['current']))[0]['reference'];
        $this->browser('DELETE', $this->path($reference))->assertNoContent()->assertHeader('Cache-Control', 'no-store, private');
        $this->browser('DELETE', $this->path($reference))->assertNoContent();
        self::assertNull($this->database->connection()->table('sessions')->find($other->sessionId()));
        self::assertNotNull($this->database->connection()->table('sessions')->find($current->sessionId()));
        $this->browserCookies = $otherCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        $this->browserCookies = $currentCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
        $this->browser('GET', '/api/v1/auth/sessions')->assertOk()->assertJsonCount(1, 'data');
    }

    public function testForeignReferencesAreNoOpsAndCurrentSessionRequiresLogout(): void
    {
        $password = bin2hex(random_bytes(24));
        $foreign = User::factory()->create(['password' => $password]);
        $this->login($foreign, $password); $foreignCookies = $this->browserCookies;
        $foreignReference = $this->browser('GET', '/api/v1/auth/sessions')->assertOk()->json('data.0.reference');
        $owner = User::factory()->create(['password' => $password]);
        $this->login($owner, $password); $ownerCookies = $this->browserCookies;
        $currentReference = $this->browser('GET', '/api/v1/auth/sessions')->assertOk()->json('data.0.reference');
        foreach ([$foreignReference, bin2hex(random_bytes(32))] as $reference) { $this->browser('DELETE', $this->path($reference))->assertNoContent(); }
        $this->browser('DELETE', $this->path($currentReference))->assertForbidden();
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
        $this->browserCookies = $foreignCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $foreign->id);
        $this->browserCookies = [];
        $this->browser('GET', '/api/v1/auth/me', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$currentReference])->assertUnauthorized();
        $this->browserCookies = $ownerCookies;
        $this->browser('GET', '/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $owner->id);
    }

    public function testSingleRevocationRequiresSessionCsrfRecentConfirmationAndStrictInput(): void
    {
        $path = $this->path(bin2hex(random_bytes(32)));
        $this->bootstrapBrowser();
        $this->browser('DELETE', $path)->assertUnauthorized();
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->login($user, $password);
        $this->browser('DELETE', $path, csrf: false)->assertStatus(419);
        $this->browser('DELETE', $path.'?user_id=1')->assertBadRequest();
        $this->browser('DELETE', $path, ['user_id' => $user->id])->assertUnprocessable();
        $this->browser('DELETE', $this->path('invalid'))->assertNotFound();
        Date::setTestNow(Date::now()->addMinutes(16));
        try { $this->browser('DELETE', $path)->assertStatus(423); }
        finally { Date::setTestNow(); }
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
    }

    public function testMutationBudgetIsPerActorEvenWhenIpChangesAndFailsClosed(): void
    {
        $password = bin2hex(random_bytes(24));
        $this->login(User::factory()->create(['password' => $password]), $password);
        $path = $this->path(bin2hex(random_bytes(32)));
        for ($i = 0; $i < 10; $i++) { $this->browser('DELETE', $path, server: ['REMOTE_ADDR' => '2001:db8::'.dechex($i + 1)])->assertNoContent(); }
        $this->browser('DELETE', $path, server: ['REMOTE_ADDR' => '2001:db8::ff'])->assertStatus(429);
        $this->app->make('config')->set('database.redis.outage', ['host' => 'redis', 'port' => 1, 'database' => 1, 'timeout' => 0.2, 'read_timeout' => 0.2]);
        $this->app->make('config')->set('cache.stores.outage', ['driver' => 'redis', 'connection' => 'outage']);
        $this->app->forgetInstance('redis');
        $original = $this->app->make(\Illuminate\Cache\RateLimiter::class);
        $unavailable = new \Illuminate\Cache\RateLimiter($this->app->make('cache')->store('outage'));
        $unavailable->for('identity-session-change', $original->limiter('identity-session-change'));
        $this->app->instance(\Illuminate\Cache\RateLimiter::class, $unavailable);
        $this->browser('DELETE', $path)->assertStatus(503);
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
    }

    public function testDirectCaseRejectsInvalidCurrentForeignAndInactiveContexts(): void
    {
        $password = bin2hex(random_bytes(24));
        $actor = $this->login(User::factory()->create(['password' => $password]), $password);
        $case = $this->app->make(RevokeSession::class);
        foreach (['invalid', str_repeat('A', 64), str_repeat('0', 63)] as $reference) {
            try { $case->handle($actor, $reference); self::fail('Malformed reference accepted.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
        try { $case->handle($actor, SessionReferenceEncoder::encode($actor->actorId, $actor->sessionId())); self::fail('Current session removed.'); }
        catch (CurrentSessionRevocationDenied) { self::assertNotNull($this->database->connection()->table('sessions')->find($actor->sessionId())); }
        foreach ([new SessionActor(User::factory()->create()->id, $actor->sessionId()), new SessionActor($actor->actorId, bin2hex(random_bytes(20)))] as $inactive) {
            try { $case->handle($inactive, bin2hex(random_bytes(32))); self::fail('Inactive actor accepted.'); }
            catch (InactiveSession) { self::assertTrue(true); }
        }
    }

    public function testSharedIpBudgetCannotBeBypassedByChangingAccounts(): void
    {
        $password = bin2hex(random_bytes(24));
        $path = $this->path(bin2hex(random_bytes(32)));
        for ($actor = 0; $actor < 3; $actor++) {
            $this->login(User::factory()->create(['password' => $password]), $password);
            for ($attempt = 0; $attempt < 10; $attempt++) { $this->browser('DELETE', $path)->assertNoContent(); }
        }
        $this->login(User::factory()->create(['password' => $password]), $password);
        $this->browser('DELETE', $path)->assertStatus(429);
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
    }

    public function testMalformedScalarAndNonemptyJsonCannotRevokeAnotherSession(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $target = $this->login($user, $password);
        $this->login($user, $password);
        $path = $this->path(SessionReferenceEncoder::encode($user->id, $target->sessionId()));
        foreach ([['{', 400], ['null', 422], ['true', 422], ['"text"', 422], ['{"all":true}', 422]] as [$content, $status]) {
            $this->call('DELETE', 'https://naturally.test'.$path, [], $this->browserCookies, [], [
                'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json',
                'HTTP_ORIGIN' => 'https://naturally.test', 'REMOTE_ADDR' => $this->browserIp,
                'HTTP_X_XSRF_TOKEN' => urldecode($this->browserCookies['XSRF-TOKEN']),
            ], $content)->assertStatus($status);
            self::assertNotNull($this->database->connection()->table('sessions')->find($target->sessionId()));
        }
    }

    public function testRevocationAfterHttpAuthenticationPreventsBothInventoryAndRemoval(): void
    {
        $this->app->instance('test.session.revoke', new class {
            public function handle(\Illuminate\Http\Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
            {
                \Illuminate\Support\Facades\DB::table('sessions')->where('id', $request->session()->getId())->delete();
                return $next($request);
            }
        });
        $password = bin2hex(random_bytes(24));
        foreach ([['identity.sessions.index', 'GET', '/api/v1/auth/sessions'], ['identity.sessions.destroy', 'DELETE', $this->path(bin2hex(random_bytes(32)))]] as [$name, $method, $path]) {
            $user = User::factory()->create(['password' => $password]);
            $this->login($user, $password);
            $this->app->make('router')->getRoutes()->getByName($name)->middleware('test.session.revoke');
            $this->browser($method, $path)->assertUnauthorized();
            self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
            $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        }
    }

    public function testRemovalVisitsSubsequentBatchesAndParticipatesInOuterRollback(): void
    {
        $password = bin2hex(random_bytes(24));
        $actor = $this->login(User::factory()->create(['password' => $password]), $password);
        $db = $this->database->connection();
        $rows = [];
        for ($i = 1; $i <= 125; $i++) { $rows[] = ['id' => str_pad(dechex($i), 40, '0', STR_PAD_LEFT), 'user_id' => $actor->actorId, 'payload' => '', 'last_activity' => Date::now()->getTimestamp()]; }
        $db->table('sessions')->insert($rows);
        $id = $rows[124]['id'];
        $reference = SessionReferenceEncoder::encode($actor->actorId, $id);
        $db->beginTransaction();
        try { $this->app->make(RevokeSession::class)->handle($actor, $reference); self::assertNull($db->table('sessions')->find($id)); }
        finally { $db->rollBack(); }
        self::assertNotNull($db->table('sessions')->find($id));
        $this->app->make(RevokeSession::class)->handle($actor, $reference);
        self::assertSame(125, $db->table('sessions')->where('user_id', $actor->actorId)->count());
    }
}
