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

    public function replies()
    {
        return $this->hasMany(Comments::class, 'parent_comments_id');
    }

    public function reply()
    {
        return $this->belongsTo(Comments::class);
    }

    // parent_comment_id is id of the comment being replied to.
    // Replies have parentcommentid set to the parent comment they belong. Parent comments don't have it (null)
}
