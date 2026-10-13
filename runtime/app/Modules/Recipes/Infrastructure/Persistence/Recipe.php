<?php

declare(strict_types=1);

namespace App\Modules\Recipes\Infrastructure\Persistence;

use App\Modules\Recipes\Domain\MealType;
use App\Modules\Recipes\Domain\RecipeCategory;
use App\Modules\Recipes\Infrastructure\Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'cooking_time_seconds' => 'integer', 'yields' => 'decimal:2', 'cost' => 'integer', 'complexity' => 'integer', 'category' => RecipeCategory::class, 'meal_type' => MealType::class];
    }

    protected static function newFactory(): RecipeFactory
    {
        return RecipeFactory::new();
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('position');
    }

    public function instructions(): HasMany
    {
        return $this->hasMany(Instruction::class)->orderBy('position');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'recipe_tags')->withTimestamps();
    }
}
