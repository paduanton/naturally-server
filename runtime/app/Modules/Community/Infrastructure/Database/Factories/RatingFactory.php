<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Database\Factories;

use App\Modules\Community\Infrastructure\Persistence\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rating> */
final class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return ['value' => 3, 'made_it' => null, 'description' => 'Synthetic feedback'];
    }

    public function between(int $userId, int $recipeId): static
    {
        return $this->state(['user_id' => $userId, 'recipe_id' => $recipeId]);
    }
}
