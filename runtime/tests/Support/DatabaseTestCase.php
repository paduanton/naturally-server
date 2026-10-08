<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Shared\Infrastructure\RuntimeApplicationFactory;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;

abstract class DatabaseTestCase extends TestCase
{
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
        $this->database->migrate();
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->database)) {
                $this->database->cleanup();
            }
        } finally {
            parent::tearDown();
        }
    }
}
