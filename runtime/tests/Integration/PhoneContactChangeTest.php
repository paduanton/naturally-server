<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ManagePhoneContacts;
use App\Modules\Identity\Application\PhoneContactSession;
use App\Modules\Identity\Application\PhoneContactNotFound;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Infrastructure\Persistence\Phone;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\BrowserTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class PhoneContactChangeTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, new PhoneContactSession($user->id, $this->app->make('session')->driver()->getId())];
    }

    public function testLabelAndPrimaryChangesArePartialAtomicAndIdempotent(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $first = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $second = $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        foreach ([0, 1] as $_) {
            $changed = $contacts->change($session, $second->id, ' Travel ', true);
            self::assertSame('Travel', $changed->label);
            self::assertTrue($changed->main);
            self::assertSame($second->number, $changed->number);
            self::assertFalse(Phone::findOrFail($first->id)->main);
            self::assertSame(1, Phone::where('user_id', $session->actorId)->where('main', true)->count());
        }
        $contacts->change($session, $first->id, 'Backup', null);
        self::assertFalse(Phone::findOrFail($first->id)->main);
        $contacts->change($session, $first->id, null, false);
        self::assertSame('Backup', Phone::findOrFail($first->id)->label);
    }

    public function testExplicitDemotionCannotRemoveThePrimaryOrPartiallyWriteItsLabel(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $phone = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        try { $contacts->change($session, $phone->id, 'Changed', false); self::fail('Primary removed by patch.'); }
        catch (\DomainException) { self::assertTrue(Phone::findOrFail($phone->id)->main); }
        self::assertSame('Mobile', Phone::findOrFail($phone->id)->label);
    }

    public function testMissingForeignAndDeletedContactsHaveTheSameFailure(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $foreign = Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create();
        $deleted = Phone::factory()->forUser($session->actorId)->create();
        $deleted->delete();
        foreach ([$foreign->id, $deleted->id, 999999] as $id) {
            try { $contacts->change($session, $id, 'Changed', null); self::fail('Non-owned contact modified.'); }
            catch (PhoneContactNotFound $exception) { self::assertNull($exception->getPrevious()); }
        }
        self::assertNotSame('Changed', $foreign->fresh()->label);
    }

    public function testRevokedAndForeignSessionsCannotPatchOwnedContacts(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $phone = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        foreach ([new PhoneContactSession(User::factory()->create()->id, $session->sessionId()), new PhoneContactSession($user->id, bin2hex(random_bytes(20)))] as $invalid) {
            try { $contacts->change($invalid, $phone->id, 'Changed', null); self::fail('Invalid session accepted.'); }
            catch (InactiveSession) { self::assertSame('Mobile', Phone::findOrFail($phone->id)->label); }
        }
    }

    public function testInvalidAndEmptyChangesNeverWrite(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $phone = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        foreach ([[null, null], ['abc', null], [str_repeat('x', 16), null], ["Work\nPhone", null]] as [$label, $main]) {
            try { $contacts->change($session, $phone->id, $label, $main); self::fail('Invalid contact patch accepted.'); }
            catch (\InvalidArgumentException) { self::assertSame('Mobile', Phone::findOrFail($phone->id)->label); }
        }
        $this->expectException(\InvalidArgumentException::class);
        $contacts->change($session, 0, 'Changed', null);
    }

    public function testOuterRollbackRestoresBothPrimaryAndLabel(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $first = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $second = $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        $db = $this->database->connection();
        $db->beginTransaction();
        try { $contacts->change($session, $second->id, 'Changed', true); self::assertFalse(Phone::findOrFail($first->id)->main); }
        finally { $db->rollBack(); }
        self::assertTrue(Phone::findOrFail($first->id)->main);
        self::assertFalse(Phone::findOrFail($second->id)->main);
        self::assertSame('Office', Phone::findOrFail($second->id)->label);
    }
}
