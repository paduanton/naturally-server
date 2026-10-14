<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Community\Infrastructure\Persistence\Follow;
use App\Modules\Community\Infrastructure\Persistence\RecipeFavorite;
use App\Modules\Community\Infrastructure\Persistence\RecipeLike;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Community/Infrastructure')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class CommunityRelationsTest extends DatabaseTestCase
{
    public function testUserRecipeEdgesAreUniqueAndDeletionDoesNotTouchAnotherUsersEdge(): void
    {
        $first = $this->createUserId();
        $second = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($first)->create();
        foreach ([RecipeLike::class, RecipeFavorite::class] as $model) {
            $edge = $model::factory()->between($first, $recipe->id)->create();
            $other = $model::factory()->between($second, $recipe->id)->create();
            self::assertSame($first, $edge->user_id);
            self::assertSame($recipe->id, $edge->recipe_id);
            $this->assertSqlRejected(fn () => $model::factory()->between($first, $recipe->id)->create(), 1062);
            self::assertTrue($edge->delete());
            self::assertSame($other->id, $model::query()->sole()->id);
            self::assertNull($model::find($edge->id));
        }
    }

    public function testFollowsAreDirectedUniqueAndCannotReferenceSelfOrMissingUsers(): void
    {
        $first = $this->createUserId();
        $second = $this->createUserId();
        $forward = Follow::factory()->between($first, $second)->create();
        $reverse = Follow::factory()->between($second, $first)->create();
        self::assertSame($first, $forward->follower_id);
        self::assertSame($second, $forward->followed_id);
        $this->assertSqlRejected(fn () => Follow::factory()->between($first, $second)->create(), 1062);
        $this->assertSqlRejected(fn () => Follow::factory()->between($first, $first)->create(), 3819);
        $this->assertSqlRejected(fn () => Follow::factory()->between($first, 999999)->create(), 1452);
        $forward->delete();
        self::assertSame($reverse->id, Follow::query()->sole()->id);
    }

    public function testRecipeEdgesRequireExistingUsersAndRecipes(): void
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        foreach ([RecipeLike::class, RecipeFavorite::class] as $model) {
            $this->assertSqlRejected(fn () => $model::factory()->between($user, 999999)->create(), 1452);
            $this->assertSqlRejected(fn () => $model::factory()->between(999999, $recipe->id)->create(), 1452);
            $rejected = false;
            try {
                (new $model())->fill(['user_id' => $user]);
            } catch (\Illuminate\Database\Eloquent\MassAssignmentException) {
                $rejected = true;
            }
            self::assertTrue($rejected);
        }
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        (new Follow())->fill(['follower_id' => $user]);
    }

    public function testPhysicalDeletionCascadesOnlyAffectedRelationsAndRollbackRestoresThem(): void
    {
        $db = $this->database->connection();
        $first = $this->createUserId();
        $second = $this->createUserId();
        $third = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($third)->create();
        foreach ([RecipeLike::class, RecipeFavorite::class] as $model) {
            $model::factory()->between($first, $recipe->id)->create();
            $model::factory()->between($second, $recipe->id)->create();
        }
        Follow::factory()->between($first, $second)->create();
        Follow::factory()->between($second, $third)->create();
        $db->beginTransaction();
        try {
            $db->table('users')->where('id', $first)->delete();
            self::assertSame(1, RecipeLike::query()->count());
            self::assertSame(1, Follow::query()->count());
            $db->rollBack();
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
        }
        self::assertSame(2, RecipeLike::query()->count());
        $db->table('users')->where('id', $first)->delete();
        self::assertSame($second, RecipeFavorite::query()->sole()->user_id);
        self::assertSame($third, Follow::query()->sole()->followed_id);
        $recipe->forceDelete();
        self::assertSame(0, RecipeLike::query()->count());
        self::assertSame(0, RecipeFavorite::query()->count());
    }

    public function testUniqueEdgeResistsOverlappingTransactionsOnIndependentMysqlConnections(): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $this->app->make('config')->set('database.connections.contention', $db->getConfig());
        $other = $this->app->make('db')->connection('contention');
        $other->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $row = ['user_id' => $user, 'recipe_id' => $recipe->id];
        try {
            $db->beginTransaction();
            $db->table('recipe_likes')->insert($row);
            $this->assertSqlRejected(fn () => $other->table('recipe_likes')->insert($row), 1205);
            $db->commit();
            $this->assertSqlRejected(fn () => $other->table('recipe_likes')->insert($row), 1062);
            self::assertSame(1, $db->table('recipe_likes')->count());
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
            $this->app->make('db')->purge('contention');
        }
    }

    public function testRelationshipMigrationCanBeRevertedWithoutDeletingItsParents(): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $migration = require __DIR__.'/../../app/Modules/Community/Infrastructure/Database/Migrations/2026_10_08_000020_create_community_relations.php';
        $migration->down();
        self::assertFalse($db->getSchemaBuilder()->hasTable('follows'));
        self::assertSame($recipe->id, $db->table('recipes')->value('id'));
        $migration->up();
        self::assertTrue($db->getSchemaBuilder()->hasTable('recipe_favorites'));
    }
}
