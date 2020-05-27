<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordResets extends Model
{   
    use SoftDeletes;
    
    protected $table = 'password_resets';

    protected $fillable = [
        'email', 'token', 'done', 'expires_at'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $dates = ['expires_at'];
}
