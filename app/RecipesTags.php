<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipesTags extends Model
{
    use SoftDeletes;

    protected $table = 'recipes_tags';

    protected $fillable = [
        'tags_id', 'recipes_id'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function tags()
    {
        return $this->belongsTo(Tags::class);
    }
    
    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }
}
