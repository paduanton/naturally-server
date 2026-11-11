<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ChangePassword;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\PasswordChangeInput;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class PasswordMutationTest extends BrowserTestCase
{
    private function authenticated(): User
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return $user;
    }

    public function testRevokedSessionCannotChangePasswordEvenAfterPriorAuthentication(): void
    {
        $user = $this->authenticated();
        $sessionId = $this->app->make('session')->driver()->getId();
        $before = $user->password;
        $this->database->connection()->table('sessions')->where('id', $sessionId)->delete();
        try {
            $this->app->make(ChangePassword::class)->handle(new PasswordChangeInput($user->id, $sessionId, bin2hex(random_bytes(24))));
            self::fail('Revoked session changed credentials.');
        } catch (InactiveSession) { self::assertTrue(hash_equals($before, $user->fresh()->password)); }
    }

    public function testForeignSessionCannotAuthorizeAnotherActorsPasswordMutation(): void
    {
        $user = $this->authenticated();
        $other = User::factory()->create();
        $sessionId = $this->app->make('session')->driver()->getId();
        $before = $other->password;
        try {
            $this->app->make(ChangePassword::class)->handle(new PasswordChangeInput($other->id, $sessionId, bin2hex(random_bytes(24))));
            self::fail('Foreign session changed credentials.');
        } catch (InactiveSession) { self::assertTrue(hash_equals($before, $other->fresh()->password)); }
    }

    public function testDomainPolicyAppliesBeforeAnyCredentialMutation(): void
    {
        $user = $this->authenticated();
        $before = $user->password;
        $sessionId = $this->app->make('session')->driver()->getId();
        try {
            $this->app->make(ChangePassword::class)->handle(new PasswordChangeInput($user->id, $sessionId, str_repeat('x', 14)));
            self::fail('Weak password accepted by application case.');
        } catch (\InvalidArgumentException) { self::assertTrue(hash_equals($before, $user->fresh()->password)); }
    }

    public function testFailureToRevokeSessionsRollsBackPasswordAndRecoveryChanges(): void
    {
        $user = $this->authenticated();
        $connection = $this->database->connection();
        $sessionId = $this->app->make('session')->driver()->getId();
        $before = $user->password;
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $connection->getSchemaBuilder()->create('session_delete_blockers', static function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->string('session_id', 255)->primary();
            $table->foreign('session_id', 'revoke_block_fk')->references('id')->on('sessions')->restrictOnDelete();
        });
        $connection->table('session_delete_blockers')->insert(['session_id' => $sessionId]);
        try {
            $this->assertSqlRejected(fn () => $this->app->make(ChangePassword::class)->handle(new PasswordChangeInput($user->id, $sessionId, bin2hex(random_bytes(24)))), 1451);
            self::assertTrue(hash_equals($before, $user->fresh()->password));
            self::assertSame(1, $connection->table('password_reset_tokens')->where('email', $user->email)->count());
            self::assertSame(1, $connection->table('sessions')->where('user_id', $user->id)->count());
        } finally { $connection->getSchemaBuilder()->drop('session_delete_blockers'); }
    }
}
