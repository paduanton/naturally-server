<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Community\Infrastructure\Persistence\Comment;
use App\Modules\Community\Infrastructure\Persistence\Rating;
use App\Modules\Community\Infrastructure\Persistence\RecipeFavorite;
use App\Modules\Community\Infrastructure\Persistence\RecipeLike;
use App\Modules\Community\Infrastructure\Persistence\Report;
use App\Modules\Identity\Infrastructure\Persistence\Phone;
use App\Modules\Identity\Infrastructure\Persistence\SocialAccount;
use App\Modules\Media\Infrastructure\Persistence\MediaAsset;
use App\Modules\Media\Infrastructure\Persistence\PdfDownload;
use App\Modules\Media\Infrastructure\Persistence\ProfileImage;
use App\Modules\Media\Infrastructure\Persistence\RatingImage;
use App\Modules\Media\Infrastructure\Persistence\RecipeImage;
use App\Modules\Recipes\Infrastructure\Persistence\Ingredient;
use App\Modules\Recipes\Infrastructure\Persistence\Instruction;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use App\Modules\Recipes\Infrastructure\Persistence\Tag;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class ModularSchemaLifecycleTest extends DatabaseTestCase
{
    private const TABLES = ['users', 'sessions', 'social_accounts', 'password_reset_tokens', 'phones', 'recipes', 'ingredients', 'instructions', 'tags', 'recipe_tags', 'recipe_likes', 'recipe_favorites', 'follows', 'ratings', 'comments', 'reports', 'media_assets', 'recipe_images', 'rating_images', 'profile_images', 'pdf_downloads'];

    public function testAllModuleTablesUseTransactionalUtf8StorageAndRepeatableMigrations(): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $kernel = $this->app->make(Kernel::class);
        self::assertSame(0, $kernel->call('migrate', ['--force' => true]));
        self::assertSame(1, $db->table('users')->count());
        foreach (self::TABLES as $table) {
            $metadata = $db->selectOne('SELECT ENGINE AS engine, TABLE_COLLATION AS table_collation FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$db->getDatabaseName(), $db->getTablePrefix().$table]);
            self::assertNotNull($metadata, $table);
            self::assertSame('InnoDB', $metadata->engine, $table);
            self::assertStringStartsWith('utf8mb4_', $metadata->table_collation, $table);
        }
        self::assertSame($user, $db->table('users')->value('id'));
    }

    public function testRollbackAndReapplyRespectAllCrossModuleForeignKeys(): void
    {
        $db = $this->database->connection();
        $this->createGraph();
        $kernel = $this->app->make(Kernel::class);
        self::assertSame(0, $kernel->call('migrate:rollback', ['--force' => true]));
        foreach (self::TABLES as $table) {
            self::assertFalse($db->getSchemaBuilder()->hasTable($table), $table);
        }
        self::assertSame(0, $db->table('migrations')->count());
        self::assertSame(0, $kernel->call('migrate', ['--force' => true]));
        foreach (self::TABLES as $table) {
            self::assertTrue($db->getSchemaBuilder()->hasTable($table), $table);
            self::assertSame(0, $db->table($table)->count(), $table);
        }
    }

    public function testCrossModuleWritesAreRolledBackTogetherOnFailure(): void
    {
        $db = $this->database->connection();
        try {
            $db->transaction(function (): void {
                $this->createGraph();
                throw new \RuntimeException('Synthetic transaction failure.');
            });
            self::fail('The transaction failure was not propagated.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Synthetic transaction failure.', $exception->getMessage());
        }
        foreach (self::TABLES as $table) {
            self::assertSame(0, $db->table($table)->count(), $table);
        }
    }

    public function testDeletingAnAccountPreservesAnotherAccountsCompleteGraph(): void
    {
        $db = $this->database->connection();
        $first = $this->createGraph();
        $second = $this->createGraph();
        $db->table('users')->where('id', $first)->delete();
        self::assertNotNull($db->table('users')->find($second));
        foreach (['social_accounts', 'phones', 'recipes', 'ratings', 'comments', 'media_assets', 'recipe_images', 'rating_images', 'profile_images'] as $table) {
            self::assertSame(1, $db->table($table)->where('user_id', $second)->count(), $table);
            self::assertSame(0, $db->table($table)->where('user_id', $first)->count(), $table);
        }
        self::assertSame(1, $db->table('ingredients')->count());
        self::assertSame(1, $db->table('instructions')->count());
        self::assertSame(1, $db->table('recipe_tags')->count());
        self::assertSame(1, $db->table('recipe_likes')->count());
        self::assertSame(1, $db->table('recipe_favorites')->count());
        self::assertSame(1, $db->table('pdf_downloads')->count());
        self::assertSame(1, $db->table('reports')->whereNull('user_id')->count());
        self::assertSame(1, $db->table('reports')->where('user_id', $second)->count());
    }

    private function createGraph(): int
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        Ingredient::factory()->forRecipe($recipe->id)->create();
        Instruction::factory()->forRecipe($recipe->id)->create();
        $recipe->tags()->attach(Tag::factory()->create()->id);
        RecipeLike::factory()->between($user, $recipe->id)->create();
        RecipeFavorite::factory()->between($user, $recipe->id)->create();
        $rating = Rating::factory()->between($user, $recipe->id)->create();
        Comment::factory()->between($user, $recipe->id)->create();
        Report::factory()->forReporter($user)->create();
        Phone::factory()->forUser($user)->asMain()->create();
        SocialAccount::factory()->forUser($user)->create();
        $asset = MediaAsset::factory()->forOwner($user)->create();
        RecipeImage::factory()->forRecipe($recipe->id, $user)->withAsset($asset->id)->create();
        RatingImage::factory()->forRating($rating->id, $user)->withAsset($asset->id)->create();
        ProfileImage::factory()->forUser($user)->withAsset($asset->id)->create();
        PdfDownload::factory()->forRecipe($recipe->id)->forUser($user)->create();
        return $user;
    }
}
