<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Database\Factories;

use App\Modules\Media\Infrastructure\Persistence\RecipeImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecipeImage> */
final class RecipeImageFactory extends Factory
{
    protected $model = RecipeImage::class;

    public function definition(): array
    {
        return ['position' => 1, 'thumbnail' => false, 'title' => null, 'alt' => null];
    }

    public function forRecipe(int $parentId, int $userId): static
    {
        return $this->state(['recipe_id' => $parentId, 'user_id' => $userId]);
    }
    public function withAsset(int $assetId): static
    {
        return $this->state(['asset_id' => $assetId]);
    }
}
