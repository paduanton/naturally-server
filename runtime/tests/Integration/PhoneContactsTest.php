<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\ManagePhoneContacts;
use App\Modules\Identity\Application\PhoneContactSession;
use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Domain\PhoneContactConflict;
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
final class PhoneContactsTest extends BrowserTestCase
{
    private function actor(): array
    {
        $password = bin2hex(random_bytes(24));
        $user = User::factory()->create(['password' => $password]);
        $this->bootstrapBrowser();
        $this->browser('POST', '/api/v1/auth/login', ['email' => $user->email, 'password' => $password])->assertNoContent();
        return [$user, new PhoneContactSession($user->id, $this->app->make('session')->driver()->getId())];
    }

    public function testContactCollectionIsPrivateBoundedAndReturnsOnlyItsPublicFields(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        self::assertSame([], $contacts->listing($session));
        Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create();
        $first = $contacts->add($session, ' Mobile ', 'br', '+5511999990001', true);
        $second = $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        self::assertSame('Mobile', $first->label);
        self::assertSame('BR', $first->countryCode);
        self::assertSame('+5511999990001', $first->number);
        self::assertTrue($first->main);
        self::assertFalse($second->main);
        self::assertSame([$first->id, $second->id], array_column($contacts->listing($session), 'id'));
        self::assertSame(['id', 'label', 'countryCode', 'number', 'main'], array_keys(get_object_vars($first)));
        self::assertSame(2, Phone::where('user_id', $user->id)->count());
        self::assertStringNotContainsString($session->sessionId(), json_encode($first));
    }

    public function testCollectionRejectsMissingOrDuplicatePrimaryAndMoreThanTwoContacts(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $this->assertConflict(fn () => $contacts->add($session, 'Mobile', 'BR', '+5511999990001', false));
        self::assertSame(0, Phone::where('user_id', $user->id)->count());
        $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $this->assertConflict(fn () => $contacts->add($session, 'Office', 'BR', '+5511999990002', true));
        $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        $this->assertConflict(fn () => $contacts->add($session, 'Other', 'BR', '+5511999990003', false));
        self::assertSame(2, Phone::where('user_id', $user->id)->count());
        self::assertSame(1, Phone::where('user_id', $user->id)->where('main', true)->count());
    }

    public function testPrimaryRemovalPromotesRemainingContactAndRepeatedDeletionIsSafe(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $first = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $second = $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        $foreign = Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create();
        $contacts->remove($session, $foreign->id);
        self::assertNotNull($foreign->fresh());
        $contacts->remove($session, $first->id);
        self::assertNotNull(Phone::withTrashed()->findOrFail($first->id)->deleted_at);
        self::assertTrue(Phone::findOrFail($second->id)->main);
        $contacts->remove($session, $first->id);
        self::assertSame([$second->id], array_column($contacts->listing($session), 'id'));
        $contacts->remove($session, $second->id);
        self::assertSame([], $contacts->listing($session));
        self::assertSame(2, Phone::onlyTrashed()->where('user_id', $user->id)->count());
    }

    public function testRemovingSecondaryContactPreservesPrimaryAndAllowsReplacement(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $primary = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $secondary = $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        $contacts->remove($session, $secondary->id);
        $replacement = $contacts->add($session, 'Other', 'BR', '+5511999990003', false);
        self::assertTrue(Phone::findOrFail($primary->id)->main);
        self::assertFalse($replacement->main);
        self::assertSame(2, Phone::where('user_id', $user->id)->count());
    }

    public function testGlobalNumberConflictsIncludingDeletedContactsDoNotLeakAnotherOwner(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $foreign = Phone::factory()->forUser(User::factory()->create()->id)->asMain()->create(['number' => '+5511999990001']);
        foreach ([false, true] as $deleted) {
            if ($deleted) { $foreign->delete(); }
            $this->assertConflict(fn () => $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true));
            self::assertSame([], $contacts->listing($session));
        }
    }

    public function testRevokedForeignDeletedAndMissingActorsCannotReadOrMutateContacts(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $phone = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $other = User::factory()->create();
        foreach ([new PhoneContactSession($other->id, $session->sessionId()), new PhoneContactSession(999999, $session->sessionId()), new PhoneContactSession($user->id, bin2hex(random_bytes(20)))] as $invalid) {
            $this->assertInactiveOperations($contacts, $invalid, $phone->id);
        }
        $user->delete();
        $this->assertInactiveOperations($contacts, $session, $phone->id);
        $user->restore();
        $this->database->connection()->table('sessions')->where('id', $session->sessionId())->delete();
        $this->assertInactiveOperations($contacts, $session, $phone->id);
        self::assertNotNull(Phone::find($phone->id));
        self::assertSame(1, Phone::where('user_id', $user->id)->count());
    }

    public function testMalformedInputsNeverReachPersistenceAndSessionCredentialsStayPrivate(): void
    {
        [$user, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        foreach ([['abc', 'BR', '+5511999990001'], [str_repeat('a', 16), 'BR', '+5511999990001'], ["Work\nPhone", 'BR', '+5511999990001'], ['Mobile', 'BRA', '+5511999990001'], ['Mobile', 'B1', '+5511999990001'], ['Mobile', 'BR', '5511999990001']] as [$label, $country, $number]) {
            try { $contacts->add($session, $label, $country, $number, true); self::fail('Malformed contact accepted.'); }
            catch (\InvalidArgumentException) { self::assertSame(0, Phone::where('user_id', $user->id)->count()); }
        }
        try { $contacts->remove($session, 0); self::fail('Invalid identifier accepted.'); }
        catch (\InvalidArgumentException) { self::assertSame([], $contacts->listing($session)); }
        self::assertStringNotContainsString($session->sessionId(), json_encode($session));
        self::assertStringNotContainsString($session->sessionId(), print_r($session, true));
        $this->expectException(\LogicException::class);
        serialize($session);
    }

    public function testOuterRollbackRevertsDeletionAndPromotionTogether(): void
    {
        [, $session] = $this->actor();
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $first = $contacts->add($session, 'Mobile', 'BR', '+5511999990001', true);
        $second = $contacts->add($session, 'Office', 'BR', '+5511999990002', false);
        $db = $this->database->connection();
        $db->beginTransaction();
        try {
            $contacts->remove($session, $first->id);
            self::assertTrue(Phone::findOrFail($second->id)->main);
        } finally { $db->rollBack(); }
        self::assertTrue(Phone::findOrFail($first->id)->main);
        self::assertFalse(Phone::findOrFail($second->id)->main);
    }

    public function testExistingContactWithoutPrimaryCannotGainAnotherSecondary(): void
    {
        [$user, $session] = $this->actor();
        Phone::factory()->forUser($user->id)->create(['number' => '+5511999990001']);
        $contacts = $this->app->make(ManagePhoneContacts::class);
        $this->assertConflict(fn () => $contacts->add($session, 'Office', 'BR', '+5511999990002', false));
        $primary = $contacts->add($session, 'Office', 'BR', '+5511999990002', true);
        self::assertTrue($primary->main);
        self::assertSame(1, Phone::where('user_id', $user->id)->where('main', true)->count());
    }

    public function testUnexpectedStorageFailureRollsBackWithoutBeingReportedAsDuplicate(): void
    {
        [$user, $session] = $this->actor();
        $db = $this->database->connection();
        $table = $db->getTablePrefix().'phones';
        $db->statement('ALTER TABLE `'.$table.'` ADD CONSTRAINT `'.$table.'_contact_test` CHECK (label <> \'Mobile\')');
        try {
            $this->app->make(ManagePhoneContacts::class)->add($session, 'Mobile', 'BR', '+5511999990001', true);
            self::fail('Storage constraint ignored.');
        } catch (\Illuminate\Database\QueryException $exception) {
            self::assertSame(3819, $exception->errorInfo[1]);
            self::assertSame(0, Phone::where('user_id', $user->id)->count());
        }
    }

    private function assertConflict(callable $operation): void
    {
        try { $operation(); self::fail('Conflicting contact accepted.'); }
        catch (PhoneContactConflict $exception) {
            self::assertSame('Phone contact cannot be added.', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    private function assertInactiveOperations(ManagePhoneContacts $contacts, PhoneContactSession $session, int $phoneId): void
    {
        foreach ([fn () => $contacts->listing($session), fn () => $contacts->add($session, 'Office', 'BR', '+5511999990002', false), fn () => $contacts->remove($session, $phoneId)] as $operation) {
            try { $operation(); self::fail('Inactive session accepted.'); }
            catch (InactiveSession) { self::assertTrue(true); }
        }
    }
}
