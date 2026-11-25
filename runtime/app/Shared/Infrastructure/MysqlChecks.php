<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Illuminate\Database\Connection;

/** Migration-only CHECK constraints with names scoped to the connection's table prefix. */
final class MysqlChecks
{
    public static function add(Connection $connection, string $table, string $name, string $expression): void
    {
        $grammar = $connection->getQueryGrammar();
        $constraint = $grammar->wrap($connection->getTablePrefix().$name);
        $connection->statement('ALTER TABLE '.$grammar->wrapTable($table).' ADD CONSTRAINT '.$constraint.' CHECK ('.$expression.')');
    }
}
