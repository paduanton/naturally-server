<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Factories;

use App\Modules\Identity\Infrastructure\Persistence\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SocialAccount> */
final class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return ['provider' => 'google', 'provider_user_id' => 'synthetic_'.bin2hex(random_bytes(16)), 'username' => null, 'profile_url' => null, 'picture_url' => null];
    }

    public function forUser(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
    public function forProvider(\App\Modules\Identity\Domain\SocialProvider $provider): static
    {
        return $this->state(['provider' => $provider]);
    }
}
