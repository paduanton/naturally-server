<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\DatabaseTestCase;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFile;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesFile;

#[CoversClass(\App\Modules\Identity\Infrastructure\IdentityServiceProvider::class)]
#[CoversFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000000_create_identity_users_table.php')]
#[CoversFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000001_create_identity_sessions_table.php')]
#[CoversFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000002_create_social_accounts_table.php')]
#[CoversFile(__DIR__.'/../../app/Modules/Identity/Infrastructure/Database/Migrations/2026_10_08_000003_create_password_reset_tokens_table.php')]
#[UsesClass(RuntimeApplicationFactory::class)]
#[UsesClass(\App\Shared\Infrastructure\RuntimeApplication::class)]
#[UsesClass(\App\Shared\Infrastructure\RuntimeConfiguration::class)]
#[UsesClass(\App\Shared\Infrastructure\LoadRuntimeEnvironment::class)]
#[UsesClass(\App\Shared\Infrastructure\RejectUnsafeRuntimeCommand::class)]
#[UsesFile(__DIR__.'/../../bootstrap/providers.php')]
#[UsesFile(__DIR__.'/../../config/app.php')]
#[UsesFile(__DIR__.'/../../config/database.php')]
#[UsesFile(__DIR__.'/../../config/cache.php')]
#[UsesFile(__DIR__.'/../../config/session.php')]
#[UsesFile(__DIR__.'/../../config/mail.php')]
#[UsesFile(__DIR__.'/../../config/logging.php')]
#[UsesFile(__DIR__.'/../../config/view.php')]
#[UsesFile(__DIR__.'/../../routes/health.php')]
final class IdentitySchemaTest extends DatabaseTestCase
{
    public function testMigrationsCreateUserStorageWithUtf8AndOptionalSocialCredentials(): void
    {
        $connection = $this->app->make('db')->connection();
        self::assertTrue($connection->getSchemaBuilder()->hasTable('users'));
        $id = $connection->table('users')->insertGetId([
            'name' => 'Ação 🥗', 'username' => 'acao', 'email' => 'acao@example.test',
        ]);
        $user = $connection->table('users')->find($id);
        self::assertSame('Ação 🥗', $user->name);
        self::assertNull($user->password);
        self::assertNull($user->email_verified_at);
        self::assertNull($user->birthday);
        self::assertNull($user->deleted_at);
        self::assertFalse($connection->getSchemaBuilder()->hasColumn('users', 'remember_token'));
    }

    #[DataProvider('duplicateIdentities')]
    public function testEmailAndUsernameRemainUniqueIncludingDeletedAccounts(string $field, string $duplicate, bool $deleted): void
    {
        $connection = $this->app->make('db')->connection();
        $connection->table('users')->insert([
            'name' => 'Chef', 'username' => 'chef', 'email' => 'chef@example.test',
            'deleted_at' => $deleted ? '2026-10-08 12:00:00' : null,
        ]);
        $other = ['name' => 'Other chef', 'username' => 'other', 'email' => 'other@example.test'];
        $other[$field] = $duplicate;
        $this->assertDatabaseRejects(fn () => $connection->table('users')->insert($other), 1062);
        self::assertSame(1, $connection->table('users')->count());
    }

    public static function duplicateIdentities(): iterable
    {
        foreach ([false, true] as $deleted) {
            $state = $deleted ? 'deleted' : 'active';
            yield 'same email '.$state => ['email', 'chef@example.test', $deleted];
            yield 'email case '.$state => ['email', 'CHEF@EXAMPLE.TEST', $deleted];
            yield 'same username '.$state => ['username', 'chef', $deleted];
            yield 'username case '.$state => ['username', 'CHEF', $deleted];
        }
    }

    public function testDatabaseRejectsMissingIdentityAndOversizedUsername(): void
    {
        $connection = $this->app->make('db')->connection();
        $this->assertDatabaseRejects(fn () => $connection->table('users')->insert([
            'name' => 'Chef', 'username' => 'chef',
        ]), 1364);
        $this->assertDatabaseRejects(fn () => $connection->table('users')->insert([
            'name' => 'Chef', 'username' => str_repeat('a', 61), 'email' => 'chef@example.test',
        ]), 1406);
        self::assertSame(0, $connection->table('users')->count());
    }

    public function testSessionsAllowGuestsRejectUnknownUsersAndDuplicateIdentifiers(): void
    {
        $connection = $this->app->make('db')->connection();
        $session = $this->sessionRow();
        $connection->table('sessions')->insert($session);
        self::assertNull($connection->table('sessions')->first()->user_id);
        $this->assertDatabaseRejects(fn () => $connection->table('sessions')->insert($session), 1062);
        $orphan = $this->sessionRow();
        $orphan['user_id'] = 999;
        $this->assertDatabaseRejects(fn () => $connection->table('sessions')->insert($orphan), 1452);
        $invalidActivity = $this->sessionRow();
        $invalidActivity['last_activity'] = -1;
        $this->assertDatabaseRejects(fn () => $connection->table('sessions')->insert($invalidActivity), 1264);
        self::assertSame(1, $connection->table('sessions')->count());
    }

    public function testPhysicalUserDeletionRemovesOnlyTheirSessions(): void
    {
        $connection = $this->app->make('db')->connection();
        $userId = $connection->table('users')->insertGetId([
            'name' => 'Chef', 'username' => 'chef', 'email' => 'chef@example.test',
        ]);
        $authenticated = $this->sessionRow();
        $authenticated['user_id'] = $userId;
        $otherId = $connection->table('users')->insertGetId([
            'name' => 'Other chef', 'username' => 'other', 'email' => 'other@example.test',
        ]);
        $otherSession = $this->sessionRow();
        $otherSession['user_id'] = $otherId;
        $connection->table('sessions')->insert([$authenticated, $otherSession, $this->sessionRow()]);
        self::assertSame(3, $connection->table('sessions')->count());
        $connection->table('users')->where('id', $userId)->delete();
        self::assertSame(0, $connection->table('sessions')->where('user_id', $userId)->count());
        self::assertSame(1, $connection->table('sessions')->where('user_id', $otherId)->count());
        self::assertSame(1, $connection->table('sessions')->whereNull('user_id')->count());
    }

    public function testUserAndSessionWritesParticipateInTheSameTransaction(): void
    {
        $connection = $this->app->make('db')->connection();
        $connection->beginTransaction();
        try {
            $userId = $connection->table('users')->insertGetId([
                'name' => 'Chef', 'username' => 'chef', 'email' => 'chef@example.test',
            ]);
            $session = $this->sessionRow();
            $session['user_id'] = $userId;
            $connection->table('sessions')->insert($session);
            $connection->rollBack();
            self::assertSame(0, $connection->table('users')->count());
            self::assertSame(0, $connection->table('sessions')->count());
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        }
    }

    public function testMigrateIsRepeatableAndRollbackRespectsDependencyOrder(): void
    {
        $connection = $this->app->make('db')->connection();
        $connection->table('users')->insert([
            'name' => 'Chef', 'username' => 'chef', 'email' => 'chef@example.test',
        ]);
        $kernel = $this->app->make(Kernel::class);
        self::assertSame(0, $kernel->call('migrate', ['--force' => true]));
        self::assertSame(1, $connection->table('users')->count());
        self::assertSame(0, $kernel->call('migrate:rollback', ['--force' => true]));
        self::assertFalse($connection->getSchemaBuilder()->hasTable('users'));
        self::assertFalse($connection->getSchemaBuilder()->hasTable('sessions'));
        self::assertSame(0, $connection->table('migrations')->count());
        self::assertSame(0, $kernel->call('migrate', ['--force' => true]));
        self::assertTrue($connection->getSchemaBuilder()->hasTable('sessions'));
        self::assertSame(0, $connection->table('users')->count());
    }

    private function sessionRow(): array
    {
        return [
            'id' => bin2hex(random_bytes(20)), 'user_id' => null,
            'payload' => base64_encode(random_bytes(32)), 'last_activity' => time(),
        ];
    }

    private function assertDatabaseRejects(callable $operation, int $expectedCode): void
    {
        $code = null;
        try {
            $operation();
        } catch (QueryException $exception) {
            $code = $exception->errorInfo[1] ?? null;
        }
        // Do not dump SQL bindings or exception contents when a constraint fails.
        self::assertSame($expectedCode, $code);
    }
}
