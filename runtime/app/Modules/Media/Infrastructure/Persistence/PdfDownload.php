<?php

declare(strict_types=1);

namespace App\Modules\Media\Infrastructure\Persistence;

use App\Modules\Media\Infrastructure\Database\Factories\PdfDownloadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


final class PdfDownload extends Model
{
    /** @use HasFactory<PdfDownloadFactory> */
    use HasFactory;


    public $timestamps = false;

    protected $table = 'pdf_downloads';
    protected $guarded = ['*'];
    protected $hidden = [];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'recipe_id' => 'integer', 'created_at' => 'immutable_datetime'];
    }

    protected static function newFactory(): PdfDownloadFactory
    {
        return PdfDownloadFactory::new();
    }

}
