<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected const ?string SCHEMA_MODULE = null;
    protected MysqlTestDatabase $database;

    public function createApplication(): Application
    {
        $application = RuntimeApplicationFactory::fromEnvironment(dirname(__DIR__, 2));
        $application->make(Kernel::class)->bootstrap();
        $this->database = new MysqlTestDatabase($application);
        return $application;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->database->migrate(static::SCHEMA_MODULE);
    }

    protected function createUserId(): int
    {
        $suffix = bin2hex(random_bytes(12));
        return $this->database->connection()->table('users')->insertGetId([
            'name' => 'Synthetic '.$suffix, 'username' => 'user_'.$suffix,
            'email' => $suffix.'@example.test',
        ]);
    }

    protected function assertSqlRejected(callable $operation, int $expectedCode): void
    {
        $code = null;
        try {
            $operation();
        } catch (\Illuminate\Database\QueryException $exception) {
            $code = $exception->errorInfo[1] ?? null;
        }
        // Assertions report only the numeric code, never SQL or bindings.
        self::assertSame($expectedCode, $code);
    }

    protected function tearDown(): void
    {
        try {
            \Illuminate\Container\Container::setInstance($this->app);
            \Illuminate\Support\Facades\Facade::clearResolvedInstances();
            \Illuminate\Support\Facades\Facade::setFacadeApplication($this->app);
            \Illuminate\Database\Eloquent\Model::setConnectionResolver($this->app->make('db'));
            if (isset($this->database)) {
                $this->database->cleanup();
            }
        } finally {
            parent::tearDown();
        }
    }
}
