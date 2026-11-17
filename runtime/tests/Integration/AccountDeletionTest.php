<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\AccountDeletionInput;
use App\Modules\Identity\Application\DeleteAccount;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class AccountDeletionTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, $this->app->make('session')->driver()->getId()];
    }

    public function testDeletingCurrentAccountIsLogicalAndRevokesOnlyItsCredentials(): void
    {
        [$user] = $this->actor();
        $other = User::factory()->create();
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $this->browser('DELETE', '/api/v1/users/me')->assertNoContent();
        self::assertNull(User::find($user->id));
        self::assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
        self::assertNotNull(User::find($other->id));
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        self::assertSame(0, $this->database->connection()->table('password_reset_tokens')->where('email', $user->email)->count());
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
    }

    public function testDeletionRequiresCsrfRecentProofAndAcceptsNoDestructiveFlags(): void
    {
        [$user] = $this->actor();
        $this->browser('DELETE', '/api/v1/users/me', csrf: false)->assertStatus(419);
        $this->browser('DELETE', '/api/v1/users/me', ['permanentlyDelete' => true])->assertUnprocessable();
        $this->browser('DELETE', '/api/v1/users/me?permanentlyDelete=true')->assertStatus(400);
        Date::setTestNow(Date::now()->addMinutes(16));
        try { $this->browser('DELETE', '/api/v1/users/me')->assertStatus(423); }
        finally { Date::setTestNow(); }
        self::assertNotNull(User::find($user->id));
    }

    public function testForeignAndRevokedSessionCannotDeleteThroughApplication(): void
    {
        [$user, $session] = $this->actor();
        $other = User::factory()->create();
        try { $this->app->make(DeleteAccount::class)->handle(new AccountDeletionInput($other->id, $session)); self::fail('Foreign account deleted.'); }
        catch (InactiveSession) { self::assertNotNull(User::find($other->id)); }
        $this->database->connection()->table('sessions')->where('id', $session)->delete();
        try { $this->app->make(DeleteAccount::class)->handle(new AccountDeletionInput($user->id, $session)); self::fail('Revoked session deleted account.'); }
        catch (InactiveSession) { self::assertNotNull(User::find($user->id)); }
    }

    public function testFailedSessionRevocationRollsBackLogicalDeletionAndRecovery(): void
    {
        [$user, $session] = $this->actor();
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $db = $this->database->connection();
        $db->getSchemaBuilder()->create('deletion_blockers', static function (Blueprint $table): void {
            $table->string('session_id', 255)->primary();
            $table->foreign('session_id', 'deletion_block_fk')->references('id')->on('sessions')->restrictOnDelete();
        });
        $db->table('deletion_blockers')->insert(['session_id' => $session]);
        try {
            $this->assertSqlRejected(fn () => $this->app->make(DeleteAccount::class)->handle(new AccountDeletionInput($user->id, $session)), 1451);
            self::assertNotNull(User::find($user->id));
            self::assertSame(1, $db->table('password_reset_tokens')->where('email', $user->email)->count());
        } finally { $db->getSchemaBuilder()->drop('deletion_blockers'); }
    }
}
