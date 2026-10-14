<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence;

use App\Modules\Media\Infrastructure\Database\Factories\MediaAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


final class MediaAsset extends Model
{
    /** @use HasFactory<MediaAssetFactory> */
    use HasFactory;


    protected $table = 'media_assets';
    protected $guarded = ['*'];
    protected $hidden = ['storage_key', 'sha256'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    protected static function newFactory(): MediaAssetFactory
    {
        return MediaAssetFactory::new();
    }

}
