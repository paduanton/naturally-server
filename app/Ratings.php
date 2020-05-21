<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ratings extends Model
{
    use SoftDeletes;

    protected $table = 'ratings';

    protected $fillable = [
        'users_id', 'recipes_id', 'made_it', 'value', 'description',
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }
    
    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }
}
