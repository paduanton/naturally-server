<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\CoversNothing;
use Illuminate\Foundation\Testing\TestCase;
use Tests\Support\MysqlTestDatabase;

#[CoversNothing]
final class DatabaseFixtureTest extends TestCase
{
    public function testRefusesNonTestingApplicationsBeforeResolvingDatabase(): void
    {
        $application = new Application();
        $application->instance('env', 'local');
        try {
            new MysqlTestDatabase($application);
            self::fail('A local application must not receive a database test fixture.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Database fixtures require the isolated testing environment.', $exception->getMessage());
            self::assertFalse($application->resolved('db'));
        }
    }

    public function testCleanupPreservesAnotherFixtureAndCanBeRepeated(): void
    {
        $firstApplication = $this->createApplication();
        $secondApplication = $this->createApplication();
        $first = new MysqlTestDatabase($firstApplication);
        $second = new MysqlTestDatabase($secondApplication);
        try {
            $first->migrate();
            $second->migrate();
            $second->connection()->table('users')->insert([
                'name' => 'Other fixture', 'username' => 'other', 'email' => 'other@example.test',
            ]);
            self::assertNotSame($first->connection()->getTablePrefix(), $second->connection()->getTablePrefix());
            $first->cleanup();
            $first->cleanup();
            self::assertFalse($first->connection()->getSchemaBuilder()->hasTable('users'));
            self::assertSame(1, $second->connection()->table('users')->count());
        } finally {
            $first->cleanup();
            $second->cleanup();
            $firstApplication->flush();
            $secondApplication->flush();
        }
    }

    public function testCleanupRefusesAChangedConnectionPrefix(): void
    {
        $application = $this->createApplication();
        $fixture = new MysqlTestDatabase($application);
        $connection = $fixture->connection();
        $prefix = $connection->getTablePrefix();
        $connection->setTablePrefix('');
        try {
            $fixture->cleanup();
            self::fail('Cleanup must reject an unowned prefix.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Refusing cleanup outside the owned database fixture.', $exception->getMessage());
        } finally {
            $connection->setTablePrefix($prefix);
            $fixture->cleanup();
            $application->flush();
        }
    }

    public function createApplication(): Application
    {
        $application = RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        $application->make(Kernel::class)->bootstrap();
        return $application;
    }

    protected function tearDown(): void
    {
        \Illuminate\Container\Container::setInstance($this->app);
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
        parent::tearDown();
    }
}
