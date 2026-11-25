<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Factories;

use App\Modules\Identity\Infrastructure\Persistence\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $suffix = bin2hex(random_bytes(12));
        return [
            'name' => 'Synthetic '.$suffix,
            'username' => 'user_'.$suffix,
            'email' => 'user_'.$suffix.'@example.test',
            // Generated per account, in memory; the model hashes before persistence.
            'password' => bin2hex(random_bytes(32)),
            'email_verified_at' => null,
            'birthday' => null,
        ];
    }

    public function socialOnly(): static
    {
        return $this->state(['password' => null]);
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => now()]);
    }
}
