<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ResetPassword;
use App\Modules\Identity\Application\PasswordResetInput;
use App\Modules\Identity\Application\RecoveryDenied;
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
final class PasswordRecoveryCaseTest extends BrowserTestCase
{
    private function token(User $user): string { return $this->app->make('auth.password')->broker()->getRepository()->create($user); }

    public function testResetConsumesNativeProofOnceAndRevokesOnlyItsOwnersSessions(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $other = User::factory()->create(['password' => $password]);
        foreach ([$user, $other] as $actor) {
            $this->browserCookies = [];
            $this->bootstrapBrowser();
            $this->browser('POST', '/api/v1/auth/login', ['email' => $actor->email, 'password' => $password])->assertNoContent();
        }
        $token = $this->token($user);
        $next = bin2hex(random_bytes(24));
        $input = new PasswordResetInput(strtoupper($user->email), $token, $next);
        $this->app->make(ResetPassword::class)->handle($input);
        self::assertTrue($this->app->make('hash')->check($next, $user->fresh()->password));
        self::assertTrue($this->app->make('hash')->check($password, $other->fresh()->password));
        self::assertSame(0, $this->database->connection()->table('sessions')->where('user_id', $user->id)->count());
        self::assertSame(1, $this->database->connection()->table('sessions')->where('user_id', $other->id)->count());
        self::assertSame(0, $this->database->connection()->table('password_reset_tokens')->count());
        $this->expectException(RecoveryDenied::class);
        $this->app->make(ResetPassword::class)->handle($input);
    }

    public function testWrongProofAndCrossAccountProofNeverMutateCredentials(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $before = $other->password;
        $token = $this->token($user);
        foreach ([[$other->email, $token], [$user->email, bin2hex(random_bytes(32))]] as [$email, $proof]) {
            try { $this->app->make(ResetPassword::class)->handle(new PasswordResetInput($email, $proof, bin2hex(random_bytes(24)))); self::fail('Invalid proof accepted.'); }
            catch (RecoveryDenied) { self::assertTrue(true); }
        }
        self::assertTrue(hash_equals($before, $other->fresh()->password));
        self::assertSame(1, $this->database->connection()->table('password_reset_tokens')->count());
    }

    public function testExpiredFutureNullAndAbsentTimestampsFailClosed(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $before = $user->password;
        foreach ([Date::now()->subMinutes(30), Date::now()->addMinute(), null] as $stamp) {
            $this->database->connection()->table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => $stamp]);
            try { $this->app->make(ResetPassword::class)->handle(new PasswordResetInput($user->email, $token, bin2hex(random_bytes(24)))); self::fail('Invalid lifetime accepted.'); }
            catch (RecoveryDenied) { self::assertTrue(hash_equals($before, $user->fresh()->password)); }
        }
        $this->database->connection()->table('password_reset_tokens')->delete();
        $this->expectException(RecoveryDenied::class);
        $this->app->make(ResetPassword::class)->handle(new PasswordResetInput($user->email, $token, bin2hex(random_bytes(24))));
    }

    public function testSocialAndDeletedAccountsCannotAcquireCredentialsThroughRecovery(): void
    {
        foreach ([User::factory()->socialOnly()->create(), User::factory()->create()] as $user) {
            $token = $this->token($user);
            if ($user->password !== null) { $user->delete(); }
            try { $this->app->make(ResetPassword::class)->handle(new PasswordResetInput($user->email, $token, bin2hex(random_bytes(24)))); self::fail('Ineligible account reset.'); }
            catch (RecoveryDenied) { self::assertTrue(true); }
        }
    }

    public function testFailureToRevokeSessionsRollsBackTokenAndPasswordChanges(): void
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        $db = $this->database->connection();
        $sessionId = $this->app->make('session')->driver()->getId();
        $token = $this->token($user);
        $before = $user->password;
        $db->getSchemaBuilder()->create('reset_blockers', static function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->string('session_id')->primary();
            $table->foreign('session_id', 'reset_session_fk')->references('id')->on('sessions')->restrictOnDelete();
        });
        $db->table('reset_blockers')->insert(['session_id' => $sessionId]);
        try {
            $this->assertSqlRejected(fn () => $this->app->make(ResetPassword::class)->handle(new PasswordResetInput($user->email, $token, bin2hex(random_bytes(24)))), 1451);
            self::assertTrue(hash_equals($before, $user->fresh()->password));
            self::assertTrue($this->app->make('auth.password')->broker()->getRepository()->exists($user, $token));
        } finally { $db->getSchemaBuilder()->drop('reset_blockers'); }
    }
}
