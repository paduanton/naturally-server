<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileImages extends Model
{
    use SoftDeletes;

    protected $table = 'profile_images';

    protected $fillable = [
        'title', 'alt', 'thumbnail', 'picture_url', 'filename',
        'path', 'mime', 'original_filename', 'original_extension'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }
}
