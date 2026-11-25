<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Database\Factories;

use App\Modules\Recipes\Infrastructure\Persistence\Instruction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Instruction> */
final class InstructionFactory extends Factory
{
    protected $model = Instruction::class;

    public function definition(): array
    {
        return ['position' => 1, 'description' => 'Synthetic instruction'];
    }

    public function forRecipe(int $recipeId): static
    {
        return $this->state(['recipe_id' => $recipeId]);
    }
}
