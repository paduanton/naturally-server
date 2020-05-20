<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Likes extends Model
{
    use SoftDeletes;

    protected $table = 'likes';

    protected $fillable = [
        'users_id', 'recipes_id', 'is_liked'
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
