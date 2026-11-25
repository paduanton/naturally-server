<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\AccountRestoreInput;
use App\Modules\Identity\Application\RestoreAccount;
use App\Modules\Identity\Infrastructure\Persistence\User;
use App\Modules\Identity\Infrastructure\RestorationTokenRepository;
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
final class AccountRestorationTest extends BrowserTestCase
{
    private function proof(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]); $user->delete();
        $token = $this->app->make(RestorationTokenRepository::class)->create($user);
        return [$user, $token, $password];
    }
    private function input(User $user, string $token, ?string $password = null): array
    {
        $password ??= bin2hex(random_bytes(24));
        return ['email' => $user->email, 'token' => $token, 'password' => $password, 'password_confirmation' => $password];
    }

    public function testProofRestoresOriginalIdentityOnceWithNewPasswordAndNoAutomaticLogin(): void
    {
        [$user, $token, $old] = $this->proof();
        $db = $this->database->connection();
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $db->table('sessions')->insert(['id' => bin2hex(random_bytes(20)), 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $next = bin2hex(random_bytes(24));
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token, $next))->assertNoContent();
        self::assertSame($user->id, User::query()->sole()->id);
        self::assertTrue($this->app->make('hash')->check($next, $user->fresh()->password));
        self::assertFalse($this->app->make('hash')->check($old, $user->fresh()->password));
        self::assertSame(0, $this->database->connection()->table('account_restoration_tokens')->count());
        self::assertSame(0, $db->table('password_reset_tokens')->where('email', $user->email)->count());
        self::assertSame(0, $db->table('sessions')->where('user_id', $user->id)->count());
        self::assertNull($user->fresh()->email_verified_at);
        self::assertCount(1, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
        $this->browser('GET', '/api/v1/auth/me')->assertUnauthorized();
        $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token, $next))->assertUnprocessable();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $old])->assertUnprocessable();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $next])->assertNoContent();
        $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token))->assertStatus(409);
    }

    public function testActiveSocialUnknownAndWrongPurposeProofsShareGenericFailures(): void
    {
        $active = User::factory()->create();
        $social = User::factory()->socialOnly()->create(); $social->delete();
        [$deleted] = $this->proof();
        $reset = $this->app->make('auth.password')->broker()->getRepository()->create($deleted);
        $this->bootstrapBrowser();
        $errors = null;
        foreach ([[$active, $this->app->make(RestorationTokenRepository::class)->create($active)], [$social, $this->app->make(RestorationTokenRepository::class)->create($social)], [$deleted, $reset], [$deleted, bin2hex(random_bytes(32))]] as [$user, $token]) {
            $response = $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token))->assertUnprocessable();
            $errors ??= $response->json('errors'); self::assertSame($errors, $response->json('errors'));
        }
        self::assertTrue(User::withTrashed()->findOrFail($deleted->id)->trashed());
        self::assertNull(User::withTrashed()->findOrFail($social->id)->password);
    }

    public function testExpiredFutureAndMissingDatesFailWithoutRestoring(): void
    {
        [$user, $token] = $this->proof();
        $this->bootstrapBrowser();
        foreach ([Date::now()->subMinutes(30), Date::now()->addMinute(), null] as $stamp) {
            $this->database->connection()->table('account_restoration_tokens')->where('email', $user->email)->update(['created_at' => $stamp]);
            $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token))->assertUnprocessable();
        }
        self::assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
    }

    public function testCsrfInputBoundsAndIpBudgetProtectConsumption(): void
    {
        [$user, $token] = $this->proof();
        $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token), csrf: false)->assertStatus(419);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token, str_repeat('x', 14)))->assertUnprocessable();
        for ($i = 0; $i < 9; $i++) { $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, bin2hex(random_bytes(32))))->assertUnprocessable(); }
        $this->browser('POST', '/api/v1/auth/account/restore', $this->input($user, $token))->assertStatus(429);
        self::assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
    }

    public function testFailedRevocationRollsBackRestorationPasswordAndProof(): void
    {
        [$user, $token] = $this->proof();
        $before = $user->password; $db = $this->database->connection(); $session = bin2hex(random_bytes(20));
        $db->table('sessions')->insert(['id' => $session, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $db->getSchemaBuilder()->create('restore_blockers', static function (Blueprint $table): void {
            $table->string('session_id', 255)->primary();
            $table->foreign('session_id', 'restore_block_fk')->references('id')->on('sessions')->restrictOnDelete();
        });
        $db->table('restore_blockers')->insert(['session_id' => $session]);
        try {
            $this->assertSqlRejected(fn () => $this->app->make(RestoreAccount::class)->handle(new AccountRestoreInput($user->email, $token, bin2hex(random_bytes(24)))), 1451);
            self::assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
            self::assertTrue(hash_equals($before, User::withTrashed()->findOrFail($user->id)->password));
            self::assertSame(1, $db->table('account_restoration_tokens')->where('email', $user->email)->count());
        } finally { $db->getSchemaBuilder()->drop('restore_blockers'); }
    }
}
