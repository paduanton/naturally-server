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

    public function recipes()
    {
        return $this->belongsToMany(Recipes::class)->wherePivot('deleted_at', null);
    }
}
