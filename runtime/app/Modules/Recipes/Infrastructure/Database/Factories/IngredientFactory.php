<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Database\Factories;

use App\Modules\Recipes\Infrastructure\Persistence\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ingredient> */
final class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition(): array
    {
        return ['position' => 1, 'measure' => '1 cup', 'description' => 'Synthetic ingredient'];
    }

    public function forRecipe(int $recipeId): static
    {
        return $this->state(['recipe_id' => $recipeId]);
    }
}
