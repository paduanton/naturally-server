<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Identity\Application\InactiveSession;
use App\Modules\Identity\Application\ManageSocialAccounts;
use App\Modules\Identity\Application\SessionActor;
use App\Modules\Identity\Domain\LastLoginMethodRemovalDenied;
use App\Modules\Identity\Domain\SocialProvider;
use App\Modules\Identity\Infrastructure\Persistence\SocialAccount;
use App\Modules\Identity\Infrastructure\Persistence\User;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Identity')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class SocialAccountsTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    public function testListsOnlyOwnedMetadataAndDeletesIdempotently(): void
    {
        $actor = $this->actor();
        $own = SocialAccount::factory()->forUser($actor->actorId)->create();
        $other = SocialAccount::factory()->forUser(User::factory()->create()->id)->create();
        $case = $this->app->make(ManageSocialAccounts::class);

        $rows = $case->listing($actor);
        self::assertCount(1, $rows);
        self::assertEquals([
            'id' => $own->id,
            'provider' => 'google',
            'linkedAt' => $own->created_at->toIso8601String(),
        ], get_object_vars($rows[0]));

        $case->remove($actor, $other->id);
        $case->remove($actor, 999999);
        self::assertNotNull($own->fresh());
        self::assertNotNull($other->fresh());

        $case->remove($actor, $own->id);
        $case->remove($actor, $own->id);
        self::assertNull($own->fresh());
        self::assertSame([], $case->listing($actor));
    }

    public function testCannotRemoveTheLastLoginMethodOfASocialOnlyAccount(): void
    {
        $actor = $this->actor();
        User::findOrFail($actor->actorId)->forceFill(['password' => null])->save();
        $first = SocialAccount::factory()->forUser($actor->actorId)->create();
        $second = SocialAccount::factory()->forUser($actor->actorId)->forProvider(SocialProvider::Facebook)->create();
        $case = $this->app->make(ManageSocialAccounts::class);

        $case->remove($actor, $first->id);
        self::assertNull($first->fresh());

        try {
            $case->remove($actor, $second->id);
            self::fail('Last login method removed.');
        } catch (LastLoginMethodRemovalDenied) {
            self::assertNotNull($second->fresh());
        }
    }

    public function testRemovalRevokesOtherOwnedSessionsAndParticipatesInRollback(): void
    {
        $actor = $this->actor();
        $link = SocialAccount::factory()->forUser($actor->actorId)->create();
        $db = $this->database->connection();
        $otherOwner = User::factory()->create();
        $otherOwnedSession = bin2hex(random_bytes(20));
        $foreignSession = bin2hex(random_bytes(20));
        $db->table('sessions')->insert([
            ['id' => $otherOwnedSession, 'user_id' => $actor->actorId, 'payload' => '', 'last_activity' => time()],
            ['id' => $foreignSession, 'user_id' => $otherOwner->id, 'payload' => '', 'last_activity' => time()],
        ]);
        $case = $this->app->make(ManageSocialAccounts::class);

        $db->beginTransaction();
        try {
            $case->remove($actor, $link->id);
            self::assertNull($db->table('sessions')->find($otherOwnedSession));
        } finally {
            $db->rollBack();
        }

        self::assertNotNull($link->fresh());
        self::assertNotNull($db->table('sessions')->find($otherOwnedSession));

        $case->remove($actor, $link->id);
        self::assertNull($db->table('sessions')->find($otherOwnedSession));
        self::assertNotNull($db->table('sessions')->find($actor->sessionId()));
        self::assertNotNull($db->table('sessions')->find($foreignSession));
    }

    public function testInactiveContextsAndInvalidIdsCannotRemoveLinks(): void
    {
        $actor = $this->actor();
        $link = SocialAccount::factory()->forUser($actor->actorId)->create();
        $case = $this->app->make(ManageSocialAccounts::class);
        $inactiveActors = [
            new SessionActor(User::factory()->create()->id, $actor->sessionId()),
            new SessionActor($actor->actorId, bin2hex(random_bytes(20))),
        ];

        foreach ($inactiveActors as $inactive) {
            try {
                $case->remove($inactive, $link->id);
                self::fail('Inactive actor accepted.');
            } catch (InactiveSession) {
                self::assertNotNull($link->fresh());
            }
        }

        $this->expectException(\InvalidArgumentException::class);
        $case->remove($actor, 0);
    }

    private function actor(): SessionActor
    {
        $user = User::factory()->create();
        $this->app->make('auth')->guard('web')->login($user);
        $session = $this->app->make('session')->driver();
        $session->save();

        return new SessionActor($user->id, $session->getId());
    }
}
