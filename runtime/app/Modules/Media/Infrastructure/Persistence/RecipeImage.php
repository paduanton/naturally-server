<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence;

use App\Modules\Media\Infrastructure\Database\Factories\RecipeImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class RecipeImage extends Model
{
    /** @use HasFactory<RecipeImageFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'recipe_images';
    protected $guarded = ['*'];
    protected $hidden = ['primary_slot', 'active_position'];

    protected function casts(): array
    {
        return ['recipe_id' => 'integer', 'user_id' => 'integer', 'asset_id' => 'integer', 'position' => 'integer', 'thumbnail' => 'boolean'];
    }

    protected static function newFactory(): RecipeImageFactory
    {
        return RecipeImageFactory::new();
    }

    public function asset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'asset_id');
    }
}
