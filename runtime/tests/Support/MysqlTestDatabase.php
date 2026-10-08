<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

/** Owns a unique table namespace inside the isolated Compose test database. */
final class MysqlTestDatabase
{
    private readonly string $name;
    private readonly string $prefix;

    public function __construct(private readonly Application $application)
    {
        if (!$application->environment('testing')) {
            throw new \RuntimeException('Database fixtures require the isolated testing environment.');
        }
        $this->name = 'foundation_'.bin2hex(random_bytes(8));
        $this->prefix = $this->name.'_';
        $this->configureReplica($application);
    }

    public function configureReplica(Application $application): void
    {
        if (!$application->environment('testing')) {
            throw new \RuntimeException('Database fixtures require the isolated testing environment.');
        }
        $configuration = $application->make('config');
        $connection = $configuration->get('database.connections.mysql');
        $connection['prefix'] = $this->prefix;
        $connection['prefix_indexes'] = true;
        $configuration->set('database.connections.'.$this->name, $connection);
        $configuration->set('database.default', $this->name);
        $configuration->set('session.connection', $this->name);
    }

    public function connection(): Connection
    {
        return $this->application->make('db')->connection($this->name);
    }

    public function migrate(): void
    {
        $container = Container::getInstance();
        $facadeApplication = Facade::getFacadeApplication();
        Container::setInstance($this->application);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->application);
        try {
            $code = $this->application->make(Kernel::class)->call('migrate', ['--force' => true, '--no-interaction' => true]);
            if ($code !== 0) {
                throw new \RuntimeException('Database fixture migration failed.');
            }
        } finally {
            Container::setInstance($container);
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($facadeApplication);
        }
    }

    public function cleanup(): void
    {
        $connection = $this->connection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getTablePrefix() !== $this->prefix) {
            throw new \RuntimeException('Refusing cleanup outside the owned database fixture.');
        }
        $schema = $connection->getSchemaBuilder();
        $tables = array_filter($schema->getTableListing(schemaQualified: false), fn (string $name): bool => str_starts_with($name, $this->prefix));
        $schema->withoutForeignKeyConstraints(function () use ($schema, $tables): void {
            foreach ($tables as $name) {
                $schema->dropIfExists(substr($name, strlen($this->prefix)));
            }
        });
    }
}
