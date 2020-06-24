<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{

    protected $table = 'tags';

    protected $fillable = [
        'hashtag'
    ];

    public function recipes_tags()
    {
        return $this->hasMany(RecipesTags::class);
    }
}
