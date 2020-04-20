<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use eloquentFilter\QueryFilter\ModelFilters\Filterable;

class Recipes extends Model
{
    use SoftDeletes, Filterable;

    protected $table = 'recipes';

    protected $fillable = [
        'users_id', 'title', 'description', 'cooking_time', 'category',
        'meal_type', 'youtube_video_url', 'yields', 'cost', 'complexity', 'notes'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    private static $whiteListFilter = [
        'cooking_time', 'meal_type', 'category', 'yields', 'cost', 'complexity'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }

    public function images()
    {
        return $this->hasMany(RecipesImages::class);
    }
}
