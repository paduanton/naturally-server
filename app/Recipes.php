<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recipes extends Model
{
    protected $table = 'recipes';

    public function users()
    {
        return $this->belongsTo('App\Users');
    }
}
