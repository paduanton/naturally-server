<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RatingsImages extends Model
{
    use SoftDeletes;

    protected $table = 'ratings_images';

    protected $fillable = [
        'thumbnail', 'picture_url', 'filename', 'path', 'mime', 'original_filename', 'original_extension'
    ];
    
    public function ratings()
    {
        return $this->belongsTo(Ratings::class);
    }
}
