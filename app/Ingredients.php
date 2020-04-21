<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ingredients extends Model
{
    protected $table = 'ingredients';
    
    protected $fillable = [
        'recipes_id', 'measure', 'description'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }
}
