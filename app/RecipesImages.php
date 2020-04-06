<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipesImages extends Model
{
    use SoftDeletes;

    protected $table = 'recipes_images';

    protected $fillable = [
        'thumbnail', 'picture_url', 'filename', 'mime', 'original_filename', 'original_extension'
    ];
}
