<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Recipes/Infrastructure/Database/Migrations')]
#[CoversClass(\App\Modules\Recipes\Infrastructure\RecipesServiceProvider::class)]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class RecipeSchemaTest extends DatabaseTestCase
{
    public function testRecipeStoresUnicodeExactYieldsAndDurationsOverOneDay(): void
    {
        $db = $this->database->connection();
        $id = $db->table('recipes')->insertGetId($this->recipe(['title' => 'Açaí 🥗', 'yields' => '2.75', 'cooking_time_seconds' => 90000, 'notes' => str_repeat('n', 2000)]));
        $row = $db->table('recipes')->find($id);
        self::assertSame('Açaí 🥗', $row->title);
        self::assertSame('2.75', $row->yields);
        self::assertSame(90000, $row->cooking_time_seconds);
        self::assertSame(2000, strlen($row->notes));
        self::assertNull($row->youtube_video_url);
        self::assertNull($row->deleted_at);
    }

    public function testRecipeRejectsOrphansInvalidScalesAndUnknownTaxonomy(): void
    {
        $db = $this->database->connection();
        $row = $this->recipe();
        $this->assertSqlRejected(fn () => $db->table('recipes')->insert(array_replace($row, ['user_id' => 999999])), 1452);
        foreach ([['cost' => 0], ['cost' => 6], ['complexity' => 0], ['complexity' => 6], ['yields' => '0.00'], ['yields' => '-1.00'], ['title' => '  '], ['description' => '']] as $invalid) {
            $this->assertSqlRejected(fn () => $db->table('recipes')->insert(array_replace($row, $invalid)), 3819);
        }
        foreach ([['category' => 'unknown'], ['meal_type' => 'unknown']] as $invalid) {
            $this->assertSqlRejected(fn () => $db->table('recipes')->insert(array_replace($row, $invalid)), 1265);
        }
        $this->assertSqlRejected(fn () => $db->table('recipes')->insert(array_replace($row, ['cooking_time_seconds' => -1])), 1264);
        self::assertSame(0, $db->table('recipes')->count());
    }

    public function testIngredientAndInstructionOrderIsPositiveUniqueAndNotTinyIntegerLimited(): void
    {
        $db = $this->database->connection();
        $recipe = $db->table('recipes')->insertGetId($this->recipe());
        $other = $db->table('recipes')->insertGetId($this->recipe());
        foreach (['ingredients', 'instructions'] as $table) {
            $row = ['recipe_id' => $recipe, 'position' => 200, 'description' => str_repeat('step ', 150)];
            if ($table === 'ingredients') {
                $row['measure'] = '1 cup';
            }
            $db->table($table)->insert($row);
            $this->assertSqlRejected(fn () => $db->table($table)->insert($row), 1062);
            $this->assertSqlRejected(fn () => $db->table($table)->insert(array_replace($row, ['position' => 0])), 3819);
            $this->assertSqlRejected(fn () => $db->table($table)->insert(array_replace($row, ['position' => 1, 'description' => ' '])), 3819);
            $this->assertSqlRejected(fn () => $db->table($table)->insert(array_replace($row, ['recipe_id' => 999999])), 1452);
            $db->table($table)->insert(array_replace($row, ['recipe_id' => $other]));
            self::assertSame(2, $db->table($table)->count());
        }
    }

    public function testRecipeSoftDeletionPreservesChildrenAndPhysicalDeletionIsIsolated(): void
    {
        $db = $this->database->connection();
        $first = $db->table('recipes')->insertGetId($this->recipe());
        $second = $db->table('recipes')->insertGetId($this->recipe());
        foreach ([$first, $second] as $id) {
            $db->table('ingredients')->insert(['recipe_id' => $id, 'position' => 1, 'measure' => '1 cup', 'description' => 'Rice']);
            $db->table('instructions')->insert(['recipe_id' => $id, 'position' => 1, 'description' => 'Cook']);
        }
        $db->table('recipes')->where('id', $first)->update(['deleted_at' => now()]);
        self::assertSame(2, $db->table('ingredients')->count());
        $db->table('recipes')->where('id', $first)->update(['deleted_at' => null]);
        self::assertSame(2, $db->table('instructions')->count());
        $db->table('recipes')->where('id', $first)->delete();
        self::assertSame($second, $db->table('ingredients')->value('recipe_id'));
        self::assertSame($second, $db->table('instructions')->value('recipe_id'));
    }

    public function testRecipeAndChildrenRollbackTogetherAndMigrationsCanBeReapplied(): void
    {
        $db = $this->database->connection();
        $row = $this->recipe();
        $db->beginTransaction();
        try {
            $id = $db->table('recipes')->insertGetId($row);
            $db->table('instructions')->insert(['recipe_id' => $id, 'position' => 1, 'description' => 'Cook']);
            $db->rollBack();
            self::assertSame(0, $db->table('recipes')->count());
            self::assertSame(0, $db->table('instructions')->count());
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
        }
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        self::assertSame(0, $kernel->call('migrate:rollback', ['--force' => true]));
        self::assertFalse($db->getSchemaBuilder()->hasTable('recipes'));
        self::assertSame(0, $kernel->call('migrate', ['--force' => true]));
        self::assertTrue($db->getSchemaBuilder()->hasTable('ingredients'));
    }

    private function recipe(array $overrides = []): array
    {
        return array_replace([
            'user_id' => $this->createUserId(), 'title' => 'Rice', 'description' => 'A simple recipe',
            'cooking_time_seconds' => 600, 'category' => 'healthy', 'meal_type' => 'lunch',
            'yields' => '2.00', 'cost' => 1, 'complexity' => 1,
        ], $overrides);
    }
}
