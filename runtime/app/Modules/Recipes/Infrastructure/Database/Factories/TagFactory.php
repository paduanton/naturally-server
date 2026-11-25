<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Database\Factories;

use App\Modules\Recipes\Infrastructure\Persistence\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tag> */
final class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return ['hashtag' => 'tag_'.bin2hex(random_bytes(12))];
    }
}
