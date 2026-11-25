<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Database\Factories;

use App\Modules\Media\Infrastructure\Persistence\RatingImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RatingImage> */
final class RatingImageFactory extends Factory
{
    protected $model = RatingImage::class;

    public function definition(): array
    {
        return ['position' => 1, 'thumbnail' => false, 'title' => null, 'alt' => null];
    }

    public function forRating(int $parentId, int $userId): static
    {
        return $this->state(['rating_id' => $parentId, 'user_id' => $userId]);
    }
    public function withAsset(int $assetId): static
    {
        return $this->state(['asset_id' => $assetId]);
    }
}
