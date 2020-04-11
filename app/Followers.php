<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Followers extends Model
{
    protected $table = 'followers';

    const CREATED_AT = 'followed_at';
    const UPDATED_AT = 'updated_at'; 
    const DELETED_AT = 'unfollowed_at';
}
