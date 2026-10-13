<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\DatabaseTestCase;
use App\Modules\Identity\Infrastructure\Database\Factories\UserFactory;
use App\Modules\Identity\Infrastructure\Persistence\User;
use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Carbon;

#[\PHPUnit\Framework\Attributes\CoversClass(User::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(UserFactory::class)]
#[\PHPUnit\Framework\Attributes\CoversFile(__DIR__.'/../../config/auth.php')]
#[\PHPUnit\Framework\Attributes\CoversFile(__DIR__.'/../../config/hashing.php')]
#[\PHPUnit\Framework\Attributes\UsesClass(\App\Shared\Infrastructure\RuntimeApplicationFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\App\Shared\Infrastructure\RuntimeApplication::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\App\Shared\Infrastructure\RuntimeConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\App\Shared\Infrastructure\LoadRuntimeEnvironment::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\App\Shared\Infrastructure\RejectUnsafeRuntimeCommand::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\App\Modules\Identity\Infrastructure\IdentityServiceProvider::class)]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../bootstrap/providers.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/app.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/database.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/cache.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/session.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/mail.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/logging.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../config/view.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../routes/health.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000000_create_identity_users_table.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000001_create_identity_sessions_table.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000002_create_social_accounts_table.php')]
#[\PHPUnit\Framework\Attributes\UsesFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000003_create_password_reset_tokens_table.php')]
final class IdentityPersistenceTest extends DatabaseTestCase
{
    public function testGuestSessionCanBePersistedWithTheRealDatabaseGuard(): void
    {
        $session = $this->app->make('session')->driver();
        $session->start();
        $marker = bin2hex(random_bytes(24));
        $session->put('probe', $marker);
        $session->save();
        $row = $this->database->connection()->table('sessions')->find($session->getId());
        self::assertNotNull($row);
        self::assertNull($row->user_id);
        self::assertFalse(str_contains($row->payload, $marker));
    }

    public function testFactoryHashesIndependentCredentialsAndSupportsSocialOnlyAccounts(): void
    {
        $plaintext = bin2hex(random_bytes(32));
        $first = User::factory()->verified()->create(['password' => $plaintext, 'birthday' => '1990-01-02']);
        $second = UserFactory::new()->create();
        $social = UserFactory::new()->socialOnly()->create();
        self::assertTrue($this->app->make('hash')->check($plaintext, $first->password));
        self::assertSame('argon2id', password_get_info($first->password)['algoName']);
        self::assertFalse(hash_equals($plaintext, $first->getRawOriginal('password')));
        self::assertFalse(hash_equals($first->password, $second->password));
        self::assertNotSame($first->email, $second->email);
        self::assertTrue($social->password === null);
        self::assertInstanceOf(\Carbon\CarbonImmutable::class, $first->email_verified_at);
        self::assertSame('1990-01-02', $first->birthday->format('Y-m-d'));
        self::assertFalse(array_key_exists('password', $first->toArray()));
        self::assertFalse(array_key_exists('deleted_at', $first->toArray()));
        self::assertSame('', $first->getRememberTokenName());
        self::assertFalse($this->app->make('hash')->needsRehash($first->password));
    }

    public function testMassAssignmentCannotSetIdentityOrSecurityAttributes(): void
    {
        $user = new User();
        $this->expectException(MassAssignmentException::class);
        $user->fill(['email_verified_at' => now(), 'id' => 999]);
    }

    public function testProviderExcludesDeletedUsersAndRejectsPasswordForSocialOnlyUsers(): void
    {
        $user = UserFactory::new()->create();
        $social = UserFactory::new()->socialOnly()->create();
        $provider = $this->app->make('auth')->guard()->getProvider();
        self::assertSame($user->id, $provider->retrieveById($user->id)->id);
        self::assertFalse($provider->validateCredentials($social, ['password' => bin2hex(random_bytes(24))]));
        $user->delete();
        self::assertNull($provider->retrieveById($user->id));
        self::assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
    }

    public function testEncryptedAuthenticatedSessionCanBeReadByAnotherApplication(): void
    {
        $user = UserFactory::new()->create();
        $session = $this->app->make('session')->driver();
        $session->start();
        $session->put('probe', bin2hex(random_bytes(24)));
        $session->save();
        $guestId = $session->getId();
        $this->app->make('auth')->guard()->login($user);
        $session->save();
        self::assertFalse(hash_equals($guestId, $session->getId()));
        self::assertTrue($this->database->connection()->table('sessions')->find($guestId) === null);
        self::assertSame($user->id, $this->database->connection()->table('sessions')->find($session->getId())->user_id);

        $replica = RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        $replica->make(Kernel::class)->bootstrap();
        $this->database->configureReplica($replica);
        try {
            $otherSession = $replica->make('session')->driver();
            $otherSession->setId($session->getId());
            $otherSession->start();
            self::assertTrue(hash_equals($session->get('probe'), $otherSession->get('probe')));
            self::assertSame($user->id, $replica->make('auth')->guard()->user()->id);
        } finally {
            $replica->make('db')->disconnect();
            $replica->flush();
        }
    }

    public function testIdleSessionExpiresAfterTwoHoursAndInvalidationDeletesStorage(): void
    {
        $session = $this->app->make('session')->driver();
        $session->start();
        $session->put('probe', bin2hex(random_bytes(24)));
        $session->save();
        $id = $session->getId();
        Carbon::setTestNow(Carbon::now()->addMinutes(121));
        try {
            self::assertSame('', $session->getHandler()->read($id));
        } finally {
            Carbon::setTestNow();
        }
        self::assertTrue($session->invalidate());
        self::assertTrue($this->database->connection()->table('sessions')->find($id) === null);
    }

    public function testRecoveryRepositoryStoresOnlyHashExpiresAndDeletesToken(): void
    {
        $user = UserFactory::new()->create();
        $repository = $this->app->make('auth.password')->broker()->getRepository();
        $token = $repository->create($user);
        $stored = $this->database->connection()->table('password_reset_tokens')->where('email', $user->email)->value('token');
        self::assertFalse(hash_equals($token, $stored));
        self::assertTrue($this->app->make('hash')->check($token, $stored));
        self::assertTrue($repository->exists($user, $token));
        self::assertFalse($repository->exists($user, bin2hex(random_bytes(24))));
        Carbon::setTestNow(Carbon::now()->addMinutes(31));
        try {
            self::assertFalse($repository->exists($user, $token));
        } finally {
            Carbon::setTestNow();
        }
        $repository->delete($user);
        self::assertFalse($repository->exists($user, $token));
    }
}
