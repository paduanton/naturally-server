<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipes extends Model
{
    use SoftDeletes;

    protected $table = 'recipes';

    protected $fillable = [
        'users_id', 'title', 'description', 'cooking_time', 'category', 'meal_type', 'video_url', 'yields', 'cost', 'complexity', 'notes'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }

}
