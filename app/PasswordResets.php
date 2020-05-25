<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordResets extends Model
{
    protected $table = 'password_resets';

    protected $fillable = [
        'email', 'token', 'done', 'expires_at', 'created_at'
    ];

}
