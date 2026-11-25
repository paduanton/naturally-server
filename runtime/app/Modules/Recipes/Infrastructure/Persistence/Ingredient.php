<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Persistence;

use App\Modules\Recipes\Infrastructure\Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['recipe_id' => 'integer', 'position' => 'integer'];
    }

    protected static function newFactory(): IngredientFactory
    {
        return IngredientFactory::new();
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
