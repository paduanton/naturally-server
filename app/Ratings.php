<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;

class Ratings extends Model
{
    use SoftDeletes, SoftCascadeTrait;

    protected $table = 'ratings';

    protected $fillable = [
        'users_id', 'recipes_id', 'made_it', 'value', 'description',
    ];

    protected $softCascade = ['images'];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }

    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }

    public function images()
    {
        return $this->hasMany(RatingsImages::class);
    }
}
