<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Recipes\Domain\MealType;
use App\Modules\Recipes\Domain\RecipeCategory;
use App\Modules\Recipes\Infrastructure\Persistence\Ingredient;
use App\Modules\Recipes\Infrastructure\Persistence\Instruction;
use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use App\Modules\Recipes\Infrastructure\Persistence\Tag;
use Illuminate\Database\Eloquent\MassAssignmentException;
use PHPUnit\Framework\Attributes\CoversDirectoryRecursively;
use PHPUnit\Framework\Attributes\UsesDirectoryRecursively;
use Tests\Support\DatabaseTestCase;

#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Recipes/Infrastructure/Persistence')]
#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Recipes/Infrastructure/Database/Factories')]
#[CoversDirectoryRecursively(__DIR__.'/../../app/Modules/Recipes/Infrastructure/Database/Migrations')]
#[UsesDirectoryRecursively(__DIR__.'/../../app')]
#[UsesDirectoryRecursively(__DIR__.'/../../config')]
#[UsesDirectoryRecursively(__DIR__.'/../../bootstrap')]
#[UsesDirectoryRecursively(__DIR__.'/../../routes')]
final class RecipePersistenceTest extends DatabaseTestCase
{
    public function testFactoriesRequireExplicitOwnershipAndPersistIndependentRecipes(): void
    {
        $owner = $this->createUserId();
        $first = Recipe::factory()->forAuthor($owner)->create(['yields' => '1.25']);
        $second = Recipe::factory()->forAuthor($owner)->create();
        self::assertSame($owner, $first->user_id);
        self::assertNotSame($first->title, $second->title);
        self::assertSame('1.25', $first->yields);
        self::assertSame(RecipeCategory::Healthy, $first->category);
        self::assertSame(MealType::Lunch, $first->meal_type);
        self::assertIsInt($first->cooking_time_seconds);
        self::assertIsInt($first->cost);
        self::assertIsInt($first->complexity);
        $this->assertSqlRejected(fn () => Recipe::factory()->create(), 1364);
    }

    public function testComponentsAreOrderedWithinTheirRecipeWithoutCrossRecipeLeakage(): void
    {
        $first = Recipe::factory()->forAuthor($this->createUserId())->create();
        $second = Recipe::factory()->forAuthor($this->createUserId())->create();
        Ingredient::factory()->forRecipe($first->id)->create(['position' => 2]);
        $ingredient = Ingredient::factory()->forRecipe($first->id)->create(['position' => 1]);
        Ingredient::factory()->forRecipe($second->id)->create(['position' => 1]);
        Instruction::factory()->forRecipe($first->id)->create(['position' => 2]);
        $instruction = Instruction::factory()->forRecipe($first->id)->create(['position' => 1]);
        Instruction::factory()->forRecipe($second->id)->create(['position' => 1]);
        self::assertSame([1, 2], $first->ingredients->pluck('position')->all());
        self::assertSame([1, 2], $first->instructions->pluck('position')->all());
        self::assertSame($first->id, $ingredient->recipe->id);
        self::assertSame($first->id, $instruction->recipe->id);
        self::assertSame(1, $second->ingredients()->count());
        self::assertSame(1, $second->instructions()->count());
    }

    public function testTagsRemainUniqueAndAttachmentIsIdempotentAndIsolated(): void
    {
        $first = Recipe::factory()->forAuthor($this->createUserId())->create();
        $second = Recipe::factory()->forAuthor($this->createUserId())->create();
        $tag = Tag::factory()->create(['hashtag' => 'healthy']);
        $other = Tag::factory()->create();
        $first->tags()->syncWithoutDetaching([$tag->id]);
        $first->tags()->syncWithoutDetaching([$tag->id]);
        $second->tags()->attach($tag->id);
        $second->tags()->attach($other->id);
        self::assertSame(1, $first->tags()->count());
        self::assertSame(2, $second->tags()->count());
        self::assertSame(2, $tag->recipes()->count());
        $this->assertSqlRejected(fn () => Tag::factory()->create(['hashtag' => 'HEALTHY']), 1062);
        $first->tags()->detach($tag->id);
        self::assertSame(2, $second->tags()->count());
        $first->forceDelete();
        self::assertSame(2, $tag->recipes()->withTrashed()->count() + $other->recipes()->withTrashed()->count());
    }

    public function testTaxonomyMatchesEveryPersistedCategoryAndMealType(): void
    {
        $owner = $this->createUserId();
        foreach (RecipeCategory::cases() as $category) {
            $recipe = Recipe::factory()->forAuthor($owner)->create(['category' => $category]);
            self::assertSame($category, $recipe->fresh()->category);
        }
        foreach (MealType::cases() as $mealType) {
            $recipe = Recipe::factory()->forAuthor($owner)->create(['meal_type' => $mealType]);
            self::assertSame($mealType, $recipe->fresh()->meal_type);
        }
    }

    public function testSoftDeleteAndRestorePreserveComponentsAndTags(): void
    {
        $recipe = Recipe::factory()->forAuthor($this->createUserId())->create();
        Ingredient::factory()->forRecipe($recipe->id)->create();
        Instruction::factory()->forRecipe($recipe->id)->create();
        $recipe->tags()->attach(Tag::factory()->create()->id);
        $recipe->delete();
        self::assertNull(Recipe::find($recipe->id));
        self::assertSame(1, $recipe->ingredients()->count());
        self::assertTrue(Recipe::withTrashed()->findOrFail($recipe->id)->restore());
        self::assertSame(1, Recipe::findOrFail($recipe->id)->instructions()->count());
        self::assertSame(1, $recipe->tags()->count());
    }

    public function testEveryPersistenceModelRejectsMassAssignmentOfOwnership(): void
    {
        foreach ([Recipe::class, Ingredient::class, Instruction::class, Tag::class] as $class) {
            $rejected = false;
            try {
                (new $class())->fill(['id' => 999, 'user_id' => 999, 'recipe_id' => 999]);
            } catch (MassAssignmentException) {
                $rejected = true;
            }
            self::assertTrue($rejected, $class);
        }
    }

    public function testTagAndJoinTableMigrationsRollbackWithoutRemovingRecipes(): void
    {
        $db = $this->database->connection();
        $recipe = Recipe::factory()->forAuthor($this->createUserId())->create();
        $path = __DIR__.'/../../app/Modules/Recipes/Infrastructure/Database/Migrations/2026_10_08_000013_create_recipe_tags_tables.php';
        $migration = require $path;
        $migration->down();
        self::assertTrue($db->getSchemaBuilder()->hasTable('recipes'));
        self::assertFalse($db->getSchemaBuilder()->hasTable('tags'));
        self::assertSame($recipe->id, $db->table('recipes')->value('id'));
        $migration->up();
        $this->assertSqlRejected(fn () => $db->table('recipe_tags')->insert(['recipe_id' => $recipe->id, 'tag_id' => 999999]), 1452);
        $this->assertSqlRejected(fn () => $db->table('tags')->insert(['hashtag' => '  ']), 3819);
    }
}
