<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Persistence;

use App\Modules\Community\Infrastructure\Database\Factories\RatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Rating extends Model
{
    /** @use HasFactory<RatingFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'ratings';
    protected $guarded = ['*'];
    protected $hidden = [];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'recipe_id' => 'integer', 'value' => 'integer', 'made_it' => 'boolean'];
    }

    protected static function newFactory(): RatingFactory
    {
        return RatingFactory::new();
    }

}
