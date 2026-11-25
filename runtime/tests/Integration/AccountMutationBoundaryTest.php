<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application as Cases;
use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class AccountMutationBoundaryTest extends BrowserTestCase
{
    public function testUnknownAccountAndAbsentRestorationProofShareTheSameFailure(): void
    {
        $user = User::factory()->create(); $user->delete(); $this->bootstrapBrowser(); $errors = null;
        foreach ([$user->email, 'missing@example.test'] as $email) {
            $password = bin2hex(random_bytes(24));
            $response = $this->browser('POST', '/api/v1/auth/account/restore', ['email' => $email, 'token' => bin2hex(random_bytes(32)), 'password' => $password, 'password_confirmation' => $password])->assertUnprocessable();
            $errors ??= $response->json('errors'); self::assertSame($errors, $response->json('errors'));
        }
        self::assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
    }

    public function testRestorationHashesTheLiteralPasswordEvenWhenItLooksPrehashed(): void
    {
        $user = User::factory()->create(); $user->delete();
        $token = $this->app->make(\App\Modules\Identity\Infrastructure\RestorationTokenRepository::class)->create($user);
        $preimage = bin2hex(random_bytes(4)); $password = $this->app->make('hash')->make($preimage);
        $this->app->make(Cases\RestoreAccount::class)->handle(new Cases\AccountRestoreInput($user->email, $token, $password));
        self::assertTrue($this->app->make('hash')->check($password, $user->fresh()->password));
        self::assertFalse($this->app->make('hash')->check($preimage, $user->fresh()->password));
    }

    private function actor(): array
    {
        $password = bin2hex(random_bytes(24)); $user = User::factory()->create(['password' => $password]);
        $this->browserCookies = []; $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, $this->app->make('session')->driver()->getId()];
    }

    public function testRevocationBetweenHttpAuthorizationAndMutationBlocksEveryWrite(): void
    {
        $this->app->instance('test.account.revoke', new class {
            public function handle(\Illuminate\Http\Request $request, \Closure $next): \Symfony\Component\HttpFoundation\Response
            {
                \Illuminate\Support\Facades\DB::table('sessions')->where('id', $request->session()->getId())->delete();
                return $next($request);
            }
        });
        foreach ([['identity.account.profile', 'PATCH', '/api/v1/users/me', ['name' => 'Changed']], ['identity.account.email', 'PUT', '/api/v1/users/me/email', ['email' => 'changed@example.test']], ['identity.account.delete', 'DELETE', '/api/v1/users/me', []]] as [$name, $method, $path, $input]) {
            [$user] = $this->actor();
            $this->app->make('router')->getRoutes()->getByName($name)->middleware('test.account.revoke');
            $this->browser($method, $path, $input)->assertUnauthorized();
            self::assertNotNull(User::find($user->id));
            self::assertSame($user->email, $user->fresh()->email);
            self::assertSame($user->name, $user->fresh()->name);
        }
    }

    public function testMissingActorCannotBeModifiedByTrustedApplicationCallers(): void
    {
        [$user, $session] = $this->actor(); $user->forceDelete();
        foreach ([[Cases\ChangeProfile::class, new Cases\ProfileChangeInput($user->id, $session, 'Changed', null)], [Cases\ChangeEmail::class, new Cases\EmailChangeInput($user->id, $session, 'new@example.test')], [Cases\DeleteAccount::class, new Cases\AccountDeletionInput($user->id, $session)]] as [$case, $input]) {
            try { $this->app->make($case)->handle($input); self::fail('Missing actor accepted.'); }
            catch (Cases\InactiveSession) { self::assertTrue(true); }
        }
    }

    public function testSocialSessionCannotChangeEmailThroughLocalCredentialProtocol(): void
    {
        $user = User::factory()->socialOnly()->create();
        $this->bootstrapBrowser(); $this->app->make('auth')->guard('web')->login($user);
        $session = $this->app->make('session')->driver(); $session->save();
        try { $this->app->make(Cases\ChangeEmail::class)->handle(new Cases\EmailChangeInput($user->id, $session->getId(), 'new@example.test')); self::fail('Social email changed without appropriate proof.'); }
        catch (Cases\CredentialChangeDenied) { self::assertSame($user->email, $user->fresh()->email); }
    }

    public function testUsernameOnlyPatchPreservesNameAndEmail(): void
    {
        [$user] = $this->actor();
        $this->browser('PATCH', '/api/v1/users/me', ['username' => 'new.name'])->assertNoContent();
        self::assertSame('new.name', $user->fresh()->username);
        self::assertSame($user->name, $user->fresh()->name);
        $this->browser('PATCH', '/api/v1/users/me', ['name' => 'Updated'])->assertNoContent();
        self::assertSame('new.name', $user->fresh()->username);
        self::assertSame('Updated', $user->fresh()->name);
        self::assertSame($user->email, $user->fresh()->email);
    }

    public function testFailedEmailRevocationRollsBackIdentityProofsAndDelivery(): void
    {
        [$user, $session] = $this->actor(); $db = $this->database->connection();
        $this->app->make('auth.password')->broker()->getRepository()->create($user);
        $db->getSchemaBuilder()->create('email_blockers', static function (Blueprint $table): void {
            $table->string('session_id', 255)->primary();
            $table->foreign('session_id', 'email_block_fk')->references('id')->on('sessions')->restrictOnDelete();
        });
        $db->table('email_blockers')->insert(['session_id' => $session]);
        try {
            $this->assertSqlRejected(fn () => $this->app->make(Cases\ChangeEmail::class)->handle(new Cases\EmailChangeInput($user->id, $session, 'new@example.test')), 1451);
            self::assertSame($user->email, $user->fresh()->email);
            self::assertSame(1, $db->table('password_reset_tokens')->where('email', $user->email)->count());
            self::assertCount(0, $this->app->make('mail.manager')->mailer()->getSymfonyTransport()->messages());
        } finally { $db->getSchemaBuilder()->drop('email_blockers'); }
    }
}
