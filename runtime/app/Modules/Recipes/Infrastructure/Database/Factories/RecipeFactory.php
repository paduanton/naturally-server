<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Database\Factories;

use App\Modules\Recipes\Infrastructure\Persistence\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Recipe> */
final class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    public function definition(): array
    {
        return ['title' => 'Synthetic recipe '.bin2hex(random_bytes(12)), 'description' => 'A synthetic recipe for tests.', 'cooking_time_seconds' => 600, 'category' => 'healthy', 'meal_type' => 'lunch', 'yields' => '2.00', 'cost' => 1, 'complexity' => 1];
    }

    public function forAuthor(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
}
