<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Followers extends Model
{
    /*
        - User 'users_id' is following user 'following_users_id'

        - The combination users_id and following_users_id is unique
    */
    protected $table = 'followers';

    protected $fillable = [
        'users_id', 'following_users_id',
    ];

    const DELETED_AT = 'unfollowed_at';


    public function following()
    {
        return $this->belongsTo(Users::class, 'users_id');
    }

    public function followers()
    {
        return $this->belongsTo(Users::class, 'following_users_id');
    }
}
