<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Database\Factories;

use App\Modules\Media\Infrastructure\Persistence\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MediaAsset> */
final class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'mime' => 'image/png',
            'disk' => 'local',
            'storage_key' => static fn (array $attributes): string => 'images/'.bin2hex(random_bytes(16)).'.'.match ($attributes['mime']) {
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => 'png',
            },
            // Synthetic metadata only; file decoding/re-encoding is a Media use case.
            'bytes' => 128, 'width' => 32, 'height' => 32,
            'sha256' => hash('sha256', random_bytes(32)),
        ];
    }

    public function forOwner(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
}
