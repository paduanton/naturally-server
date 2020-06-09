<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Followers extends Model
{
    /*
        - The user 'users_id' is following the user 'following_users_id'

        - The combination users_id and following_users_id is unique
    */

    use SoftDeletes;

    protected $table = 'followers';

    public $timestamps = false;

    protected $fillable = [
        'users_id', 'following_users_id',
    ];

    const DELETED_AT = 'unfollowed_at';

    public function follower()
    {
        return $this->belongsTo(Users::class, 'users_id');
    }

    public function followed()
    {
        return $this->belongsTo(Users::class, 'following_users_id');
    }
}
