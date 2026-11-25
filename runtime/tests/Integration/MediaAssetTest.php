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
final class MediaAssetTest extends DatabaseTestCase
{
    public function testAssetFactoryRequiresAnOwnerAndStoresOnlyInternalMetadata(): void
    {
        $user = $this->createUserId();
        $first = MediaAsset::factory()->forOwner($user)->create();
        $second = MediaAsset::factory()->forOwner($user)->create();
        self::assertSame($user, $first->user_id);
        self::assertIsInt($first->bytes);
        self::assertIsInt($first->width);
        self::assertIsInt($first->height);
        self::assertNotSame($first->storage_key, $second->storage_key);
        self::assertFalse(array_key_exists('storage_key', $first->toArray()));
        self::assertFalse(array_key_exists('sha256', $first->toArray()));
        self::assertSame([], array_intersect(['picture_url', 'original_filename', 'access_token'], $this->database->connection()->getSchemaBuilder()->getColumnListing('media_assets')));
        $this->assertSqlRejected(fn () => MediaAsset::factory()->create(), 1364);
        $this->assertSqlRejected(fn () => MediaAsset::factory()->forOwner($user)->create(['storage_key' => $first->storage_key]), 1062);
    }

    public function testMetadataRejectsInvalidFormatsSizesDimensionsAndTraversal(): void
    {
        $user = $this->createUserId();
        foreach ([['bytes' => 0], ['width' => 0], ['height' => 0], ['storage_key' => '../avatar.jpg'], ['storage_key' => 'https://external.test/image.jpg'], ['sha256' => str_repeat('z', 64)], ['storage_key' => 'images/'.bin2hex(random_bytes(16)).'.jpg']] as $invalid) {
            $this->assertSqlRejected(fn () => MediaAsset::factory()->forOwner($user)->create($invalid), 3819);
        }
        $this->assertSqlRejected(fn () => MediaAsset::factory()->forOwner($user)->create(['mime' => 'image/gif']), 1265);
        $this->assertSqlRejected(fn () => MediaAsset::factory()->forOwner(999999)->create(), 1452);
        foreach (['image/jpeg', 'image/png', 'image/webp'] as $mime) {
            self::assertSame($mime, MediaAsset::factory()->forOwner($user)->create(['mime' => $mime])->fresh()->mime);
        }
    }


}
