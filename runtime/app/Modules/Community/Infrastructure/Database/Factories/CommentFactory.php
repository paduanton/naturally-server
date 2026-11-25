<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Database\Factories;

use App\Modules\Community\Infrastructure\Persistence\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comment> */
final class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return ['description' => 'Synthetic comment', 'parent_id' => null];
    }

    public function between(int $userId, int $recipeId): static
    {
        return $this->state(['user_id' => $userId, 'recipe_id' => $recipeId]);
    }
    public function replyTo(int $parentId): static
    {
        return $this->state(['parent_id' => $parentId]);
    }
}
