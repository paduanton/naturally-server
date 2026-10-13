<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Persistence;

use App\Modules\Recipes\Infrastructure\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tags')->withTimestamps();
    }
}
