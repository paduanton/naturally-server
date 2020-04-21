<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comments extends Model
{
    use SoftDeletes;

    protected $table = 'comments';

    protected $fillable = [
        'users_id', 'recipes_id', 'parent_comments_id', 'description'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    
}
