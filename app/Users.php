<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Users extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'username',
        'email', 'password', 'birthday'
    ];


    protected $hidden = [
        'password', 'remember_token', 'deleted_at'
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function recipes()
    {
        return $this->hasMany(Recipes::class);
    }

    public function social_networks()
    {
        return $this->hasMany(SocialNetWorks::class);
    }

    public function images()
    {
        return $this->hasMany(UsersImages::class);
    }

    public function following()
    {
        return $this->belongsToMany(Users::class, 'followers', 'users_id', 'following_users_id')->withTimestamps()->withPivot('id');
    }
}
