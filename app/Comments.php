<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use eloquentFilter\QueryFilter\ModelFilters\Filterable;

class Comments extends Model
{
    use SoftDeletes, SoftCascadeTrait, Filterable;

    protected $table = 'comments';

    protected $fillable = [
        'users_id', 'recipes_id', 'parent_comments_id', 'description'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $softCascade = ['replies'];

    public function replies()
    {
        return $this->hasMany(Comments::class, 'parent_comments_id');
    }

    public function reply()
    {
        return $this->belongsTo(Comments::class);
    }
}
