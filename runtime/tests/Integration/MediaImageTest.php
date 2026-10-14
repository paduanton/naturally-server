<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Community\Infrastructure\Persistence\Rating;
use App\Modules\Media\Infrastructure\Persistence\MediaAsset;
use App\Modules\Media\Infrastructure\Persistence\ProfileImage;
use App\Modules\Media\Infrastructure\Persistence\RatingImage;
use App\Modules\Media\Infrastructure\Persistence\RecipeImage;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Media/Infrastructure')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class MediaImageTest extends DatabaseTestCase
{
    public function testRecipeImagesMustMatchBothAssetAndRecipeOwners(): void
    {
        $first = $this->createUserId();
        $second = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($first)->create();
        $asset = MediaAsset::factory()->forOwner($first)->create();
        $foreignAsset = MediaAsset::factory()->forOwner($second)->create();
        $image = RecipeImage::factory()->forRecipe($recipe->id, $first)->withAsset($asset->id)->create();
        self::assertSame($recipe->id, $image->recipe_id);
        self::assertSame($asset->id, $image->asset->id);
        self::assertFalse($image->thumbnail);
        $this->assertSqlRejected(fn () => RecipeImage::factory()->forRecipe($recipe->id, $first)->withAsset($foreignAsset->id)->create(['position' => 2]), 1452);
        $this->assertSqlRejected(fn () => RecipeImage::factory()->forRecipe($recipe->id, $second)->withAsset($foreignAsset->id)->create(['position' => 2]), 1452);
        $this->assertSqlRejected(fn () => RecipeImage::factory()->forRecipe($recipe->id, $first)->withAsset(999999)->create(['position' => 2]), 1452);
    }

    public function testRatingImagesCannotBorrowAnotherRatingOrAssetOwner(): void
    {
        $first = $this->createUserId();
        $second = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($first)->create();
        $rating = Rating::factory()->between($second, $recipe->id)->create();
        $asset = MediaAsset::factory()->forOwner($second)->create();
        $foreignAsset = MediaAsset::factory()->forOwner($first)->create();
        $image = RatingImage::factory()->forRating($rating->id, $second)->withAsset($asset->id)->create();
        self::assertSame($rating->id, $image->rating_id);
        self::assertSame($asset->id, $image->asset->id);
        $this->assertSqlRejected(fn () => RatingImage::factory()->forRating($rating->id, $first)->withAsset($foreignAsset->id)->create(['position' => 2]), 1452);
        $this->assertSqlRejected(fn () => RatingImage::factory()->forRating($rating->id, $second)->withAsset($foreignAsset->id)->create(['position' => 2]), 1452);
    }

    public function testOnlyOneActiveThumbnailAndOneActivePositionExistPerRecipeOrRating(): void
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $rating = Rating::factory()->between($user, $recipe->id)->create();
        foreach ([[RecipeImage::class, 'forRecipe', $recipe->id], [RatingImage::class, 'forRating', $rating->id]] as [$model, $state, $parent]) {
            $first = $model::factory()->$state($parent, $user)->withAsset(MediaAsset::factory()->forOwner($user)->create()->id)->create(['thumbnail' => true]);
            $secondAsset = MediaAsset::factory()->forOwner($user)->create();
            $factory = $model::factory()->$state($parent, $user)->withAsset($secondAsset->id);
            $this->assertSqlRejected(fn () => $factory->create(['position' => 2, 'thumbnail' => true]), 1062);
            $this->assertSqlRejected(fn () => $factory->create(['position' => 1, 'thumbnail' => false]), 1062);
            $this->assertSqlRejected(fn () => $factory->create(['position' => 0]), 3819);
            $first->delete();
            $second = $factory->create(['position' => 1, 'thumbnail' => true]);
            self::assertSame(1, $second->position);
            self::assertFalse(array_key_exists('primary_slot', $second->toArray()));
            self::assertFalse(array_key_exists('active_position', $second->toArray()));
            $this->assertSqlRejected(fn () => $first->restore(), 1062);
        }
    }

    public function testProfileImagesRequireTheirUsersAssetAndAllowReplacingDeletedPrimary(): void
    {
        $user = $this->createUserId();
        $other = $this->createUserId();
        $asset = MediaAsset::factory()->forOwner($user)->create();
        $first = ProfileImage::factory()->forUser($user)->withAsset($asset->id)->create();
        self::assertSame($user, $first->user_id);
        self::assertSame($asset->id, $first->asset->id);
        self::assertTrue($first->thumbnail);
        $foreignAsset = MediaAsset::factory()->forOwner($other)->create();
        $this->assertSqlRejected(fn () => ProfileImage::factory()->forUser($user)->withAsset($foreignAsset->id)->create(['thumbnail' => false]), 1452);
        $secondAsset = MediaAsset::factory()->forOwner($user)->create();
        $this->assertSqlRejected(fn () => ProfileImage::factory()->forUser($user)->withAsset($secondAsset->id)->create(), 1062);
        $first->delete();
        $second = ProfileImage::factory()->forUser($user)->withAsset($secondAsset->id)->create();
        self::assertTrue($second->thumbnail);
        self::assertFalse(array_key_exists('primary_slot', $second->toArray()));
    }

    public function testAttachmentDeletionPreservesAssetsAndOwnerDeletionCascadesOnlyOwnedMedia(): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $other = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $asset = MediaAsset::factory()->forOwner($user)->create();
        RecipeImage::factory()->forRecipe($recipe->id, $user)->withAsset($asset->id)->create();
        $preserved = MediaAsset::factory()->forOwner($other)->create();
        ProfileImage::factory()->forUser($other)->withAsset($preserved->id)->create();
        $recipe->forceDelete();
        self::assertSame(0, RecipeImage::query()->count());
        self::assertNotNull(MediaAsset::find($asset->id));
        $db->table('users')->where('id', $user)->delete();
        self::assertSame($preserved->id, MediaAsset::query()->sole()->id);
        self::assertSame($other, ProfileImage::query()->sole()->user_id);
        $preserved->delete();
        self::assertSame(0, ProfileImage::query()->count());
    }

    public function testAllMediaModelsRejectMassAssignmentOfOwnerAssetOrStoragePath(): void
    {
        foreach ([MediaAsset::class, RecipeImage::class, RatingImage::class, ProfileImage::class] as $model) {
            $rejected = false;
            try {
                (new $model())->fill(['user_id' => 999, 'asset_id' => 999, 'storage_key' => '../file.jpg']);
            } catch (\Illuminate\Database\Eloquent\MassAssignmentException) {
                $rejected = true;
            }
            self::assertTrue($rejected);
        }
    }
}
