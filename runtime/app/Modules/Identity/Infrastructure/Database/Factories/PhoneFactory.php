<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Factories;

use App\Modules\Identity\Infrastructure\Persistence\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Phone> */
final class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    public function definition(): array
    {
        return ['main' => false, 'label' => 'mobile', 'country_code' => 'US', 'number' => '+1999'.(string) random_int(1000000, 9999999)];
    }

    public function forUser(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
    public function asMain(): static
    {
        return $this->state(['main' => true]);
    }
}
