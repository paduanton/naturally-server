<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Media\Infrastructure\Persistence\PdfDownload;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Media/Infrastructure')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class PdfDownloadPersistenceTest extends DatabaseTestCase
{
    public function testDownloadFactorySupportsGuestsAndAuthenticatedUsersWithoutClientFingerprint(): void
    {
        $owner = $this->createUserId();
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($owner)->create();
        $guest = PdfDownload::factory()->forRecipe($recipe->id)->create();
        $authenticated = PdfDownload::factory()->forRecipe($recipe->id)->forUser($user)->create();
        self::assertNull($guest->user_id);
        self::assertSame($user, $authenticated->user_id);
        self::assertSame($recipe->id, $authenticated->recipe_id);
        self::assertInstanceOf(\Carbon\CarbonImmutable::class, $guest->created_at);
        $columns = $this->database->connection()->getSchemaBuilder()->getColumnListing('pdf_downloads');
        self::assertSame([], array_intersect(['ip', 'user_agent', 'cookie', 'token', 'updated_at'], $columns));
        $this->assertSqlRejected(fn () => PdfDownload::factory()->forRecipe(999999)->create(), 1452);
    }

    public function testRemovingDownloaderAnonymizesOnlyTheirEventsAndDeletingRecipeRemovesItsEvents(): void
    {
        $db = $this->database->connection();
        $owner = $this->createUserId();
        $user = $this->createUserId();
        $other = $this->createUserId();
        $first = Recipe::factory()->forAuthor($owner)->create();
        $second = Recipe::factory()->forAuthor($owner)->create();
        $ownEvent = PdfDownload::factory()->forRecipe($first->id)->forUser($user)->create();
        $preserved = PdfDownload::factory()->forRecipe($second->id)->forUser($other)->create();
        $db->table('users')->where('id', $user)->delete();
        self::assertNull($ownEvent->fresh()->user_id);
        self::assertSame($other, $preserved->fresh()->user_id);
        self::assertSame(2, PdfDownload::query()->count());
        $first->forceDelete();
        self::assertSame($preserved->id, PdfDownload::query()->sole()->id);
    }

    public function testDownloadEventsRollbackWithTheirTransactionAndCannotBeMassAssigned(): void
    {
        $db = $this->database->connection();
        $recipe = Recipe::factory()->forAuthor($this->createUserId())->create();
        $db->beginTransaction();
        try {
            PdfDownload::factory()->forRecipe($recipe->id)->create();
            $db->rollBack();
            self::assertSame(0, PdfDownload::query()->count());
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
        }
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        (new PdfDownload())->fill(['user_id' => 999, 'recipe_id' => $recipe->id]);
    }

    public function testDownloadMigrationCanBeReappliedWithoutChangingRecipeData(): void
    {
        $db = $this->database->connection();
        $recipe = Recipe::factory()->forAuthor($this->createUserId())->create();
        $migration = require __DIR__.'/../../app/Modules/Media/Infrastructure/Database/Migrations/2026_10_08_000032_create_pdf_downloads_table.php';
        $migration->down();
        self::assertFalse($db->getSchemaBuilder()->hasTable('pdf_downloads'));
        self::assertSame($recipe->id, $db->table('recipes')->value('id'));
        $migration->up();
        self::assertTrue($db->getSchemaBuilder()->hasTable('pdf_downloads'));
    }
}
