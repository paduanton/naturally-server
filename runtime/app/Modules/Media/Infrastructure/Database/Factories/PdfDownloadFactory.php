<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Database\Factories;

use App\Modules\Media\Infrastructure\Persistence\PdfDownload;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PdfDownload> */
final class PdfDownloadFactory extends Factory
{
    protected $model = PdfDownload::class;

    public function definition(): array
    {
        return ['user_id' => null, 'created_at' => now()];
    }

    public function forRecipe(int $recipeId): static
    {
        return $this->state(['recipe_id' => $recipeId]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(['user_id' => $userId]);
    }
}
