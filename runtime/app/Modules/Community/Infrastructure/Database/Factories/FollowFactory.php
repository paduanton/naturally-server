<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Database\Factories;

use App\Modules\Community\Infrastructure\Persistence\Follow;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Follow> */
final class FollowFactory extends Factory
{
    protected $model = Follow::class;

    public function definition(): array
    {
        return [];
    }

    public function between(int $followerId, int $followedId): static
    {
        return $this->state(['follower_id' => $followerId, 'followed_id' => $followedId]);
    }
}
