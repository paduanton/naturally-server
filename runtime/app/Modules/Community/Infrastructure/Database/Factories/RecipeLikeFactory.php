<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Database\Factories;

use App\Modules\Community\Infrastructure\Persistence\RecipeLike;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecipeLike> */
final class RecipeLikeFactory extends Factory
{
    protected $model = RecipeLike::class;

    public function definition(): array
    {
        return [];
    }

    public function between(int $userId, int $recipeId): static
    {
        return $this->state(['user_id' => $userId, 'recipe_id' => $recipeId]);
    }
}
