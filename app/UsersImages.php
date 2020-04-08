<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersImages extends Model
{
    use SoftDeletes;

    protected $table = 'users_images';

    protected $fillable = [
        'thumbnail', 'picture_url', 'filename', 'path', 'mime', 'original_filename', 'original_extension'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }
}
