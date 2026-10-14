<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Persistence;

use App\Modules\Community\Infrastructure\Database\Factories\RecipeLikeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


final class RecipeLike extends Model
{
    /** @use HasFactory<RecipeLikeFactory> */
    use HasFactory;


    protected $table = 'recipe_likes';
    protected $guarded = ['*'];
    protected $hidden = [];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'recipe_id' => 'integer'];
    }

    protected static function newFactory(): RecipeLikeFactory
    {
        return RecipeLikeFactory::new();
    }

}
