<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Database\Factories;

use App\Modules\Media\Infrastructure\Persistence\ProfileImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfileImage> */
final class ProfileImageFactory extends Factory
{
    protected $model = ProfileImage::class;

    public function definition(): array
    {
        return ['thumbnail' => true, 'title' => null, 'alt' => null];
    }

    public function forUser(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
    public function withAsset(int $assetId): static
    {
        return $this->state(['asset_id' => $assetId]);
    }
}
