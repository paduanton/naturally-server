<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ChangeEmail;
use App\Modules\Identity\Application\EmailChangeInput;
use App\Modules\Identity\Application\AccountDetailsUnavailable;
use App\Modules\Identity\Application\InactiveSession;
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
final class EmailMutationTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password, 'email_verified_at' => Date::now()]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, $this->app->make('session')->driver()->getId()];
    }

    public function testChangeRevokesSessionsAndOldRecoveryAndSendsNewVerification(): void
    {
        [$user, $session] = $this->actor();
        $old = $user->email;
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        self::assertTrue($this->app->make(ChangeEmail::class)->handle(new EmailChangeInput($user->id, $session, ' NEW@Example.Test ')));
        self::assertSame('new@example.test', $user->fresh()->email);
        self::assertNull($user->fresh()->email_verified_at);
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        self::assertSame(0, $this->database->connection()->table('password_reset_tokens')->where('email', $old)->count());
        self::assertCount(1, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }

    public function testUnchangedEmailKeepsVerificationAndSessionWithoutMail(): void
    {
        [$user, $session] = $this->actor();
        self::assertFalse($this->app->make(ChangeEmail::class)->handle(new EmailChangeInput($user->id, $session, strtoupper($user->email))));
        self::assertNotNull($user->fresh()->email_verified_at);
        self::assertSame(1, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        self::assertCount(0, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }

    public function testVerificationDeliveryFollowsOuterTransactionRollback(): void
    {
        [$user, $session] = $this->actor();
        $old = $user->email;
        $db = $this->database->connection();
        $db->beginTransaction();
        try {
            $this->app->make(ChangeEmail::class)->handle(new EmailChangeInput($user->id, $session, 'new@example.test'));
            self::assertCount(0, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
        } finally { $db->rollBack(); }
        self::assertSame($old, $user->fresh()->email);
        self::assertSame(1, $db->table('sessions')->where('user_id', $user->id)->count());
        self::assertCount(0, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
    }

    public function testForeignRevokedAndConflictingChangesCannotModifyAccount(): void
    {
        [$user, $session] = $this->actor();
        $other = User::factory()->create();
        $before = $user->email;
        try { $this->app->make(ChangeEmail::class)->handle(new EmailChangeInput($other->id, $session, 'new@example.test')); self::fail('Foreign session accepted.'); }
        catch (InactiveSession) { self::assertNotSame('new@example.test', $other->fresh()->email); }
        try { $this->app->make(ChangeEmail::class)->handle(new EmailChangeInput($user->id, $session, $other->email)); self::fail('Duplicate email accepted.'); }
        catch (AccountDetailsUnavailable $exception) { self::assertNull($exception->getPrevious()); }
        self::assertSame($before, $user->fresh()->email);
        $this->database->connection()->table('sessions')->where('id', $session)->delete();
        $this->expectException(InactiveSession::class);
        $this->app->make(ChangeEmail::class)->handle(new EmailChangeInput($user->id, $session, 'new@example.test'));
    }
}
