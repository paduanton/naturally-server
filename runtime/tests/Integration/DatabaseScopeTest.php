<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\Support\DatabaseTestCase;
use Tests\Support\MysqlTestDatabase;

#[CoversNothing]
final class DatabaseScopeTest extends DatabaseTestCase
{
    protected const ?string SCHEMA_MODULE = 'Identity';

    public function testIdentityFixturesExcludeUnrelatedModuleTables(): void
    {
        $schema = $this->database->connection()->getSchemaBuilder();
        self::assertTrue($schema->hasTable('users'));
        self::assertTrue($schema->hasTable('phones'));
        self::assertFalse($schema->hasTable('recipes'));
        self::assertFalse($schema->hasTable('media_assets'));
    }

    public function testRecipeFixturesIncludeTheirIdentityDependencyOnly(): void
    {
        $fixture = new MysqlTestDatabase($this->app);
        try {
            $fixture->migrate('Recipes');
            $schema = $fixture->connection()->getSchemaBuilder();
            foreach (['users', 'phones', 'recipes', 'tags', 'recipe_tags'] as $table) {
                self::assertTrue($schema->hasTable($table));
            }
            self::assertFalse($schema->hasTable('ratings'));
            self::assertFalse($schema->hasTable('media_assets'));
        } finally {
            $fixture->cleanup();
        }
    }

    public function testUnknownProfilesFailBeforeRunningMigrations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->database->migrate('Unknown');
    }
}
