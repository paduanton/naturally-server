<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Domain\PhoneNumber;
use App\Modules\Identity\Domain\SocialProvider;
use App\Modules\Identity\Infrastructure\Persistence\Phone;
use App\Modules\Identity\Infrastructure\Persistence\SocialAccount;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity/Infrastructure/Persistence')]
#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Factories')]
#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Casts')]
#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity/Domain')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class IdentityContactPersistenceTest extends DatabaseTestCase
{
    public function testPhoneCastAndStorageAcceptTheSameCanonicalBounds(): void
    {
        $user = $this->createUserId();
        foreach (['+12', '+123456789012345'] as $input) {
            $phone = Phone::factory()->forUser($user)->create(['number' => $input]);
            self::assertSame($input, $phone->fresh()->number->value);
        }
    }

    public function testPhoneFactoryUsesExplicitOwnerCanonicalValueAndProtectedSerialization(): void
    {
        $user = $this->createUserId();
        $first = Phone::factory()->forUser($user)->asMain()->create();
        $second = Phone::factory()->forUser($user)->create();
        self::assertSame($user, $first->user_id);
        self::assertTrue($first->main);
        self::assertFalse($second->main);
        self::assertInstanceOf(PhoneNumber::class, $first->number);
        self::assertNotSame($first->number->value, $second->number->value);
        self::assertFalse(array_key_exists('number', $first->toArray()));
        self::assertFalse(array_key_exists('primary_slot', $first->toArray()));
        $typed = PhoneNumber::fromString('+19990000001');
        $second->number = $typed;
        $second->save();
        self::assertSame($typed->value, $second->fresh()->number->value);
    }

    public function testPhoneCastRejectsNonCanonicalWriteAndRestorePreservesOwnership(): void
    {
        $phone = Phone::factory()->forUser($this->createUserId())->asMain()->create();
        $phone->delete();
        self::assertNull(Phone::find($phone->id));
        self::assertTrue($phone->restore());
        self::assertSame($phone->user_id, Phone::query()->sole()->user_id);
        $this->expectException(\InvalidArgumentException::class);
        $phone->number = 'unformatted';
    }

    public function testSocialIdentityFactorySupportsAllProvidersWithoutCredentialsOrEmail(): void
    {
        $user = $this->createUserId();
        foreach (SocialProvider::cases() as $provider) {
            $account = SocialAccount::factory()->forUser($user)->forProvider($provider)->create(['provider_user_id' => 'Opaque-00042']);
            self::assertSame($provider, $account->fresh()->provider);
            self::assertSame('Opaque-00042', $account->provider_user_id);
            self::assertSame($user, $account->user_id);
            self::assertFalse(array_key_exists('provider_user_id', $account->toArray()));
            self::assertSame([], array_intersect(['password', 'email', 'access_token', 'refresh_token', 'token_secret'], array_keys($account->toArray())));
        }
        self::assertSame(3, SocialAccount::query()->count());
    }

    public function testUserDeletionCascadesOnlyTheirContactsWhileLogicalDeletionPreservesThem(): void
    {
        $db = $this->database->connection();
        $first = $this->createUserId();
        $second = $this->createUserId();
        foreach ([$first, $second] as $user) {
            Phone::factory()->forUser($user)->asMain()->create();
            SocialAccount::factory()->forUser($user)->create();
        }
        $db->table('users')->where('id', $first)->update(['deleted_at' => now()]);
        self::assertSame(2, Phone::query()->count());
        self::assertSame(2, SocialAccount::query()->count());
        $db->table('users')->where('id', $first)->delete();
        self::assertSame($second, Phone::query()->sole()->user_id);
        self::assertSame($second, SocialAccount::query()->sole()->user_id);
    }

    public function testContactModelsRejectMassAssignmentOfOwnershipAndProviderIdentity(): void
    {
        foreach ([Phone::class, SocialAccount::class] as $model) {
            $rejected = false;
            try {
                (new $model())->fill(['user_id' => 999, 'provider' => 'google', 'provider_user_id' => 'changed']);
            } catch (\Illuminate\Database\Eloquent\MassAssignmentException) {
                $rejected = true;
            }
            self::assertTrue($rejected);
        }
    }
}
