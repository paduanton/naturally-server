<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ListSessions;
use App\Modules\Identity\Application\SessionActor;
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
final class SessionInventoryTest extends BrowserTestCase
{
    private function actor(): SessionActor
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return new SessionActor($user->id, $this->app->make('session')->driver()->getId());
    }

    private function stored(?int $owner, int $activity): string
    {
        $id = bin2hex(random_bytes(20));
        $this->database->connection()->table('sessions')->insert(['id' => $id, 'user_id' => $owner, 'payload' => base64_encode('private-session-data'), 'last_activity' => $activity, 'ip_address' => '192.0.2.1', 'user_agent' => 'PrivateAgent']);
        return $id;
    }

    public function testPrivatePaginationExcludesForeignAnonymousAndExpiredRows(): void
    {
        $actor = $this->actor();
        $now = Date::now()->getTimestamp();
        $currentActivity = $now - 2;
        $this->database->connection()->table('sessions')
            ->where('id', $actor->sessionId())
            ->update(['last_activity' => $currentActivity]);

        $ids = [$actor->sessionId()];
        for ($i = 1; $i <= 25; $i++) {
            $ids[] = $this->stored($actor->actorId, $currentActivity - $i);
        }
        $this->stored(User::factory()->create()->id, $now);
        $this->stored(null, $now);
        $this->stored($actor->actorId, $now - (config('session.lifetime') * 60) - 1);
        $first = $this->browser('GET', '/api/v1/auth/sessions', csrf: false)->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 26)->assertJsonPath('meta.current_page', 1)->assertJsonPath('meta.per_page', 20);
        self::assertStringContainsString('no-store', $first->headers->get('Cache-Control'));
        self::assertTrue($first->json('data.0.current'));
        foreach ($first->json('data') as $row) {
            self::assertSame(['reference', 'last_seen_at', 'current'], array_keys($row));
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $row['reference']);
            self::assertMatchesRegularExpression('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00\z/', $row['last_seen_at']);
        }
        foreach (array_merge($ids, ['private-session-data', 'PrivateAgent', '192.0.2.1']) as $secret) { self::assertStringNotContainsString($secret, $first->getContent()); }
        $second = $this->browser('GET', '/api/v1/auth/sessions?page=2')->assertOk()->assertJsonCount(6, 'data')->assertJsonPath('meta.current_page', 2);
        self::assertSame([], array_intersect(array_column($first->json('data'), 'reference'), array_column($second->json('data'), 'reference')));
        $this->browser('GET', '/api/v1/auth/sessions?page=1000000&per_page=100')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 26);
    }

    public function testPaginationAndAuthenticationAreBounded(): void
    {
        $this->browser('GET', '/api/v1/auth/sessions')->assertUnauthorized();
        $actor = $this->actor();
        foreach (['page=0', 'page=1000001', 'page[]=1', 'page=1.1', 'per_page=0', 'per_page=101', 'user_id=2'] as $query) { $this->browser('GET', '/api/v1/auth/sessions?'.$query)->assertUnprocessable(); }
        foreach ([[0, 20], [1000001, 20], [1, 0], [1, 101]] as [$page, $size]) {
            try { $this->app->make(ListSessions::class)->handle($actor, $page, $size); self::fail('Unbounded pagination.'); }
            catch (\InvalidArgumentException) { self::assertTrue(true); }
        }
        $this->browser('GET', '/api/v1/auth/sessions?per_page=1')->assertOk()->assertJsonCount(1, 'data');
    }

    public function testCasesRequireTheCurrentLiveSessionOfAnUndeletedOwner(): void
    {
        $actor = $this->actor();
        $other = User::factory()->create();
        $expired = $this->stored($actor->actorId, Date::now()->getTimestamp() - (config('session.lifetime') * 60) - 1);
        foreach ([new SessionActor($other->id, $actor->sessionId()), new SessionActor($actor->actorId, bin2hex(random_bytes(20))), new SessionActor($actor->actorId, $expired)] as $invalid) {
            try { $this->app->make(ListSessions::class)->handle($invalid); self::fail('Inactive actor accepted.'); }
            catch (InactiveSession) { self::assertTrue(true); }
        }
        User::findOrFail($actor->actorId)->delete();
        $this->expectException(InactiveSession::class);
        $this->app->make(ListSessions::class)->handle($actor);
    }

    public function testSessionCredentialsCannotBeSerializedOrAccidentallyExposed(): void
    {
        $actor = $this->actor();
        self::assertSame(['actorId' => $actor->actorId], json_decode(json_encode($actor, JSON_THROW_ON_ERROR), true));
        ob_start(); var_dump($actor); $debug = ob_get_clean();
        self::assertStringNotContainsString($actor->sessionId(), $debug);
        self::assertStringContainsString('[REDACTED]', $debug);
        $this->expectException(\LogicException::class);
        serialize($actor);
    }
}
