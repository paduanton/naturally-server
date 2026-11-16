<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ChangeProfile;
use App\Modules\Identity\Application\ProfileChangeInput;
use App\Modules\Identity\Application\AccountDetailsUnavailable;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class ProfileMutationTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, $this->app->make('session')->driver()->getId()];
    }

    public function testProfileNormalizesOnlySuppliedFieldsAndReturnsSafeAccountData(): void
    {
        [$user, $session] = $this->actor();
        $email = $user->email;
        $input = new ProfileChangeInput($user->id, $session, ' Nome 🥗 ', ' USER.Name ');
        foreach ([0, 1] as $_) {
            $result = $this->app->make(ChangeProfile::class)->handle($input);
            self::assertSame('Nome 🥗', $result->name);
            self::assertSame('user.name', $result->username);
        }
        self::assertSame($email, $user->fresh()->email);
        self::assertFalse(str_contains(json_encode($result), $session));
    }

    public function testForeignAndRevokedSessionsCannotModifyProfile(): void
    {
        [$user, $session] = $this->actor();
        $other = User::factory()->create();
        foreach ([$other->id, $user->id] as $actor) {
            if ($actor === $user->id) { $this->database->connection()->table('sessions')->where('id', $session)->delete(); }
            try { $this->app->make(ChangeProfile::class)->handle(new ProfileChangeInput($actor, $session, 'Changed', null)); self::fail('Unauthorized profile update.'); }
            catch (InactiveSession) { self::assertNotSame('Changed', User::findOrFail($actor)->name); }
        }
    }

    public function testConflictingDeletedUsernameRollsBackTheWholePatch(): void
    {
        [$user, $session] = $this->actor();
        $other = User::factory()->create(['username' => 'taken.name']);
        $other->delete();
        $before = $user->name;
        try { $this->app->make(ChangeProfile::class)->handle(new ProfileChangeInput($user->id, $session, 'Changed', 'taken.name')); self::fail('Reserved username reused.'); }
        catch (AccountDetailsUnavailable $exception) { self::assertNull($exception->getPrevious()); }
        self::assertSame($before, $user->fresh()->name);
    }

    public function testEmptyAndInvalidPatchesFailBeforePersistence(): void
    {
        [$user, $session] = $this->actor();
        foreach ([[null, null], [' ', null], [null, 'bad..name']] as [$name, $username]) {
            try { $this->app->make(ChangeProfile::class)->handle(new ProfileChangeInput($user->id, $session, $name, $username)); self::fail('Invalid profile accepted.'); }
            catch (\InvalidArgumentException) { self::assertSame($user->name, $user->fresh()->name); }
        }
    }
}
