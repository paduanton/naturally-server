<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersFavoritesRecipes extends Model
{
    use SoftDeletes;

    protected $table = 'users_favorites_recipes';

    protected $fillable = [
        'users_id', 'recipes_id'
    ];

    protected $hidden = [
        'deleted_at'
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
