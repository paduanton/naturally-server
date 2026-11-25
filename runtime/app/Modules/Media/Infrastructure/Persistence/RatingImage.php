<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence;

use App\Modules\Media\Infrastructure\Database\Factories\RatingImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class RatingImage extends Model
{
    /** @use HasFactory<RatingImageFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'rating_images';
    protected $guarded = ['*'];
    protected $hidden = ['primary_slot', 'active_position'];

    protected function casts(): array
    {
        return ['rating_id' => 'integer', 'user_id' => 'integer', 'asset_id' => 'integer', 'position' => 'integer', 'thumbnail' => 'boolean'];
    }

    protected static function newFactory(): RatingImageFactory
    {
        return RatingImageFactory::new();
    }

    public function asset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'asset_id');
    }
}
