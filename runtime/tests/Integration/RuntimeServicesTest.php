<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

// Operational acceptance uses real services; the application coverage suite is separate.
#[CoversNothing]
final class RuntimeServicesTest extends TestCase
{
    public function createApplication(): Application
    {
        $application = RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        $application->make(Kernel::class)->bootstrap();
        if (!$application->environment('testing')) {
            throw new \RuntimeException('Service acceptance requires the isolated testing environment.');
        }

        return $application;
    }

    public function testMysqlAuthenticatesAndSupportsUtf8Transactions(): void
    {
        $connection = $this->app->make('db')->connection();
        self::assertSame('naturally', $connection->selectOne('SELECT DATABASE() AS name')->name);
        self::assertStringStartsWith('8.4.', $connection->selectOne('SELECT VERSION() AS version')->version);
        $connection->statement('CREATE TEMPORARY TABLE runtime_probe (id INT PRIMARY KEY, value VARCHAR(100))');
        try {
            $connection->beginTransaction();
            $connection->insert('INSERT INTO runtime_probe VALUES (?, ?)', [1, 'ação 🥗']);
            $connection->rollBack();
            self::assertSame(0, (int) $connection->selectOne('SELECT COUNT(*) AS total FROM runtime_probe')->total);
            $connection->transaction(fn () => $connection->insert('INSERT INTO runtime_probe VALUES (?, ?)', [2, 'ação 🥗']));
            self::assertSame('ação 🥗', $connection->selectOne('SELECT value FROM runtime_probe WHERE id = 2')->value);
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            $connection->statement('DROP TEMPORARY TABLE runtime_probe');
        }
    }

    public function testRedisCacheExpiresAndReleasesRealLocks(): void
    {
        $cache = $this->app->make('cache')->store();
        $key = 'runtime-probe:'.bin2hex(random_bytes(12));
        $first = $cache->lock($key.':lock', 10);
        $second = $cache->lock($key.':lock', 10);
        try {
            $cache->put($key, ['value' => 'ação'], 1);
            self::assertSame(['value' => 'ação'], $cache->get($key));
            self::assertTrue($first->get());
            self::assertFalse($second->get());
            self::assertTrue($first->release());
            self::assertTrue($second->get());
            usleep(1_100_000);
            self::assertNull($cache->get($key));
        } finally {
            $cache->forget($key);
            $first->release();
            $second->release();
        }
    }

    public function testApplicationDatabaseAccountCannotReadAdministrativeCredentials(): void
    {
        $denied = false;
        try {
            $this->app->make('db')->connection()->select('SELECT User FROM mysql.user');
        } catch (\Illuminate\Database\QueryException $exception) {
            $denied = in_array($exception->errorInfo[1] ?? null, [1044, 1142], true);
        }
        self::assertTrue($denied);
    }
}
