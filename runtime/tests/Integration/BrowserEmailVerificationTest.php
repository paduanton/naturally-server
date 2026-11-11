<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class BrowserEmailVerificationTest extends BrowserTestCase
{
    public function testVerificationEventFollowsOuterCommitAndRollbackLeavesNoVerifiedState(): void
    {
        $user = User::factory()->create();
        $case = $this->app->make(\App\Modules\Identity\Application\VerifyEmail::class);
        $input = new \App\Modules\Identity\Application\EmailVerificationInput($user->id, $user->id, sha1($user->email));
        $events = 0;
        Event::listen(Verified::class, static function () use (&$events): void { $events++; });
        $db = $this->database->connection();
        $db->beginTransaction();
        try { $case->handle($input); self::assertSame(0, $events); }
        finally { $db->rollBack(); }
        self::assertNull($user->fresh()->email_verified_at);
        self::assertSame(0, $events);
        $db->beginTransaction();
        try { $case->handle($input); self::assertSame(0, $events); $db->commit(); }
        finally { if ($db->transactionLevel() > 0) { $db->rollBack(); } }
        self::assertNotNull($user->fresh()->email_verified_at);
        self::assertSame(1, $events);
    }

    public function testDeletedAccountCannotBeVerifiedThroughApplicationCase(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $this->expectException(\App\Modules\Identity\Application\EmailVerificationDenied::class);
        $this->app->make(\App\Modules\Identity\Application\VerifyEmail::class)->handle(new \App\Modules\Identity\Application\EmailVerificationInput($user->id, $user->id, sha1($user->email)));
    }

    private function register(): User
    {
        $this->bootstrapBrowser();
        $suffix = bin2hex(random_bytes(4));
        $password = bin2hex(random_bytes(24));
        $this->browser('POST', '/api/v1/auth/register', ['name' => 'Synthetic Name', 'username' => 'user'.$suffix, 'email' => $suffix.'@example.test', 'password' => $password, 'password_confirmation' => $password])->assertCreated();
        return User::query()->sole();
    }

    private function path(): string
    {
        $messages = $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages();
        self::assertCount(1, $messages);
        $html = $messages->first()->getOriginalMessage()->getHtmlBody();
        preg_match('~https://naturally\.test/api/v1/auth/email/verify/[^"<>\s]+~', $html, $matches);
        self::assertTrue(isset($matches[0]), 'Signed verification link missing.');
        $parts = parse_url(html_entity_decode($matches[0]));
        return $parts['path'].'?'.$parts['query'];
    }

    public function testRegistrationSendsSignedLinkAndVerificationIsIdempotent(): void
    {
        $user = $this->register();
        self::assertNull($user->email_verified_at);
        $path = $this->path();
        $events = 0;
        Event::listen(Verified::class, static function () use (&$events): void { $events++; });
        $this->browser('GET', $path)->assertNoContent();
        $this->browser('GET', $path)->assertNoContent();
        self::assertNotNull($user->fresh()->email_verified_at);
        self::assertSame(1, $events);
        $this->browser('GET', '/api/v1/auth/me')->assertJsonPath('data.email_verified', true);
    }

    public function testForeignActorCannotUseAnotherAccountsValidLink(): void
    {
        $user = $this->register();
        $path = $this->path();
        $password = bin2hex(random_bytes(24));
        $other = User::factory()->create(['password' => $password]);
        $this->browserCookies = [];
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $other->email, 'password' => $password])->assertNoContent();
        $this->browser('GET', $path)->assertForbidden();
        self::assertNull($user->fresh()->email_verified_at);
        self::assertNull($other->fresh()->email_verified_at);
    }

    public function testExpiredTamperedAndSupersededEmailProofsFail(): void
    {
        $user = $this->register();
        $path = $this->path();
        $this->browser('GET', str_replace(sha1($user->email), str_repeat('0', 40), $path))->assertForbidden();
        Date::setTestNow(Date::now()->addMinutes(61));
        try { $this->browser('GET', $path)->assertForbidden(); }
        finally { Date::setTestNow(); }
        $user->forceFill(['email' => bin2hex(random_bytes(8)).'@example.test'])->save();
        $this->browser('GET', $path)->assertForbidden();
        self::assertNull($user->fresh()->email_verified_at);
    }

    public function testUnavailableMailTransportDoesNotTurnPersistedRegistrationIntoFailure(): void
    {
        $this->app->make('config')->set('mail.default', 'smtp');
        $this->app->make('config')->set('mail.mailers.smtp', ['transport' => 'smtp', 'host' => 'redis', 'port' => 1, 'timeout' => 0.2, 'require_tls' => false]);
        $user = $this->register();
        self::assertNull($user->email_verified_at);
        $this->browser('GET', '/api/v1/auth/me')->assertOk();
    }
}
