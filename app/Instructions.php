<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instructions extends Model
{
    use SoftDeletes;

    protected $table = 'instructions';

    protected $fillable = [
        'recipes_id', 'order', 'description'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }
      
}
