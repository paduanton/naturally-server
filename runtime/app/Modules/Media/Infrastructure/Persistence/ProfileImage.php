<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence;

use App\Modules\Media\Infrastructure\Database\Factories\ProfileImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ProfileImage extends Model
{
    /** @use HasFactory<ProfileImageFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'profile_images';
    protected $guarded = ['*'];
    protected $hidden = ['primary_slot'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'asset_id' => 'integer', 'thumbnail' => 'boolean'];
    }

    protected static function newFactory(): ProfileImageFactory
    {
        return ProfileImageFactory::new();
    }

    public function asset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'asset_id');
    }
}
