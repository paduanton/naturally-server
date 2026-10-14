<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Persistence;

use App\Modules\Community\Infrastructure\Database\Factories\RecipeFavoriteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


final class RecipeFavorite extends Model
{
    /** @use HasFactory<RecipeFavoriteFactory> */
    use HasFactory;


    protected $table = 'recipe_favorites';
    protected $guarded = ['*'];
    protected $hidden = [];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'recipe_id' => 'integer'];
    }

    protected static function newFactory(): RecipeFavoriteFactory
    {
        return RecipeFavoriteFactory::new();
    }

}
