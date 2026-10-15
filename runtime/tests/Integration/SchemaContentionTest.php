<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Community\Infrastructure\Persistence\Rating;
use App\Modules\Media\Infrastructure\Persistence\MediaAsset;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DatabaseTestCase;

#[CoversNothing]
final class SchemaContentionTest extends DatabaseTestCase
{
    #[DataProvider('uniqueRelationships')]
    public function testOnlyOneConflictingRelationshipSurvivesOverlappingTransactions(string $table): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $otherUser = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $rating = Rating::factory()->between($user, $recipe->id)->create();
        $firstAsset = MediaAsset::factory()->forOwner($user)->create();
        $secondAsset = MediaAsset::factory()->forOwner($user)->create();
        [$first, $second] = match ($table) {
            'follows' => array_fill(0, 2, ['follower_id' => $user, 'followed_id' => $otherUser]),
            'recipe_likes', 'recipe_favorites' => array_fill(0, 2, ['user_id' => $user, 'recipe_id' => $recipe->id]),
            'ratings' => array_fill(0, 2, ['user_id' => $otherUser, 'recipe_id' => $recipe->id, 'value' => 3]),
            'phones' => [
                ['user_id' => $user, 'main' => true, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+19990000001'],
                ['user_id' => $user, 'main' => true, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+19990000002'],
            ],
            'profile_images' => [
                ['user_id' => $user, 'asset_id' => $firstAsset->id, 'thumbnail' => true],
                ['user_id' => $user, 'asset_id' => $secondAsset->id, 'thumbnail' => true],
            ],
            'recipe_images' => [
                ['user_id' => $user, 'recipe_id' => $recipe->id, 'asset_id' => $firstAsset->id, 'position' => 1, 'thumbnail' => true],
                ['user_id' => $user, 'recipe_id' => $recipe->id, 'asset_id' => $secondAsset->id, 'position' => 2, 'thumbnail' => true],
            ],
            'rating_images' => [
                ['user_id' => $user, 'rating_id' => $rating->id, 'asset_id' => $firstAsset->id, 'position' => 1, 'thumbnail' => true],
                ['user_id' => $user, 'rating_id' => $rating->id, 'asset_id' => $secondAsset->id, 'position' => 2, 'thumbnail' => true],
            ],
        };
        $this->app->make('config')->set('database.connections.contention', $db->getConfig());
        $other = $this->app->make('db')->connection('contention');
        $other->statement('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            $db->beginTransaction();
            $db->table($table)->insert($first);
            $this->assertSqlRejected(fn () => $other->table($table)->insert($second), 1205);
            $db->rollBack();
            self::assertTrue($other->table($table)->insert($second));
            $this->assertSqlRejected(fn () => $db->table($table)->insert($first), 1062);
            // The rating fixture precedes the competing rating written by another user.
            $query = $db->table($table);
            if ($table === 'ratings') {
                $query->where('user_id', $otherUser);
            }
            self::assertSame(1, $query->count());
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
            $this->app->make('db')->purge('contention');
        }
    }

    public static function uniqueRelationships(): iterable
    {
        foreach (['follows', 'recipe_likes', 'recipe_favorites', 'ratings', 'phones', 'profile_images', 'recipe_images', 'rating_images'] as $table) {
            yield $table => [$table];
        }
    }
}
