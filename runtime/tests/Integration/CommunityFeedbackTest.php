<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Community\Infrastructure\Persistence\Comment;
use App\Modules\Community\Infrastructure\Persistence\Rating;
use App\Modules\Community\Infrastructure\Persistence\Report;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Community/Infrastructure')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class CommunityFeedbackTest extends DatabaseTestCase
{
    public function testRatingFactoryPersistsOneRatingPerUserAndRecipeWithStrictScale(): void
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $rating = Rating::factory()->between($user, $recipe->id)->create(['made_it' => true, 'value' => 5]);
        self::assertSame($user, $rating->user_id);
        self::assertSame($recipe->id, $rating->recipe_id);
        self::assertSame(5, $rating->value);
        self::assertTrue($rating->made_it);
        $this->assertSqlRejected(fn () => Rating::factory()->between($user, $recipe->id)->create(), 1062);
        foreach ([0, 6] as $value) {
            $this->assertSqlRejected(fn () => Rating::factory()->between($this->createUserId(), $recipe->id)->create(['value' => $value]), 3819);
        }
        $this->assertSqlRejected(fn () => $this->database->connection()->table('ratings')->insert(['user_id' => $this->createUserId(), 'recipe_id' => $recipe->id, 'value' => 3, 'made_it' => 2]), 3819);
        $other = Rating::factory()->between($this->createUserId(), $recipe->id)->create();
        self::assertNull($other->made_it);
    }

    public function testDeletingAndRestoringRatingPreservesItsIdentityAndUniqueness(): void
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $rating = Rating::factory()->between($user, $recipe->id)->create();
        $rating->delete();
        self::assertNull(Rating::find($rating->id));
        $this->assertSqlRejected(fn () => Rating::factory()->between($user, $recipe->id)->create(), 1062);
        self::assertTrue($rating->restore());
        self::assertSame($rating->id, Rating::query()->sole()->id);
    }

    public function testRepliesCannotCrossRecipeBoundariesEvenThroughDirectDatabaseWrites(): void
    {
        $user = $this->createUserId();
        $first = Recipe::factory()->forAuthor($user)->create();
        $second = Recipe::factory()->forAuthor($user)->create();
        $root = Comment::factory()->between($user, $first->id)->create();
        $reply = Comment::factory()->between($user, $first->id)->replyTo($root->id)->create();
        self::assertSame($root->id, $reply->parent->id);
        self::assertSame($reply->id, $root->replies()->sole()->id);
        $this->assertSqlRejected(fn () => Comment::factory()->between($user, $second->id)->replyTo($root->id)->create(), 1452);
        $this->assertSqlRejected(fn () => Comment::factory()->between($user, $first->id)->replyTo(999999)->create(), 1452);
        $this->assertSqlRejected(fn () => Comment::factory()->between($user, $first->id)->create(['description' => '  ']), 3819);
        self::assertSame(2, Comment::query()->count());
    }

    public function testCommentsSupportLongUnicodeTextAndLogicalRestorePreservesReplies(): void
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        $text = str_repeat('Ação 🥗 ', 100);
        $root = Comment::factory()->between($user, $recipe->id)->create(['description' => $text]);
        $reply = Comment::factory()->between($this->createUserId(), $recipe->id)->replyTo($root->id)->create();
        self::assertSame($text, $root->fresh()->description);
        $root->delete();
        self::assertNotNull(Comment::find($reply->id));
        self::assertTrue($root->restore());
        self::assertSame($root->id, $reply->fresh()->parent->id);
        $root->forceDelete();
        self::assertNull(Comment::find($reply->id));
    }

    public function testAnonymousAndAuthenticatedReportsPreserveTriageAndHideContactFields(): void
    {
        $db = $this->database->connection();
        $user = $this->createUserId();
        $anonymous = Report::factory()->create(['email' => 'synthetic@example.test', 'reporter_name' => 'Synthetic reporter']);
        $authenticated = Report::factory()->forReporter($user)->create();
        self::assertNull($anonymous->user_id);
        self::assertSame($user, $authenticated->user_id);
        self::assertFalse(array_key_exists('email', $anonymous->toArray()));
        self::assertFalse(array_key_exists('reporter_name', $anonymous->toArray()));
        $db->table('users')->where('id', $user)->delete();
        self::assertNull($authenticated->fresh()->user_id);
        self::assertSame(2, Report::query()->count());
        foreach (['bug', 'suggestion', 'idea', 'partnership', 'application_improvement', 'other'] as $category) {
            self::assertSame($category, Report::factory()->create(['category' => $category])->fresh()->category);
        }
        $this->assertSqlRejected(fn () => Report::factory()->create(['category' => 'unknown']), 1265);
        $this->assertSqlRejected(fn () => Report::factory()->create(['title' => ' ']), 3819);
        $this->assertSqlRejected(fn () => Report::factory()->create(['description' => ' ']), 3819);
        $anonymous->delete();
        self::assertNull(Report::find($anonymous->id));
        self::assertTrue($anonymous->restore());
    }

    public function testFeedbackReferencesMustExistAndOwnershipCannotBeMassAssigned(): void
    {
        $user = $this->createUserId();
        $recipe = Recipe::factory()->forAuthor($user)->create();
        foreach ([Comment::class, Rating::class] as $model) {
            $this->assertSqlRejected(fn () => $model::factory()->between($user, 999999)->create(), 1452);
            $this->assertSqlRejected(fn () => $model::factory()->between(999999, $recipe->id)->create(), 1452);
        }
        foreach ([Comment::class, Rating::class, Report::class] as $model) {
            $rejected = false;
            try {
                (new $model())->fill(['user_id' => $user]);
            } catch (\Illuminate\Database\Eloquent\MassAssignmentException) {
                $rejected = true;
            }
            self::assertTrue($rejected);
        }
    }

    public function testDeletingRecipeCascadesFeedbackOnlyForThatRecipe(): void
    {
        $user = $this->createUserId();
        $first = Recipe::factory()->forAuthor($user)->create();
        $second = Recipe::factory()->forAuthor($user)->create();
        foreach ([Comment::class, Rating::class] as $model) {
            $model::factory()->between($user, $first->id)->create();
            $model::factory()->between($user, $second->id)->create();
        }
        $first->forceDelete();
        self::assertSame($second->id, Comment::query()->sole()->recipe_id);
        self::assertSame($second->id, Rating::query()->sole()->recipe_id);
    }
}
