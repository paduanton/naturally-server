<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Users extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name', 'username', 'email', 'password', 'birthday', 'email_verified_at'
    ];

    protected $hidden = [
        'password', 'remember_token', 'deleted_at', 'pivot'
    ];

    public function recipes()
    {
        return $this->hasMany(Recipes::class);
    }

    public function social_network_accounts()
    {
        return $this->hasMany(SocialNetworkAccounts::class);
    }

    public function images()
    {
        return $this->hasMany(ProfileImages::class);
    }

    public function followers()
    {
        return $this->belongsToMany(Users::class, 'followers', 'following_users_id', 'users_id');
    }

    public function following()
    {
        return $this->belongsToMany(Users::class, 'followers', 'users_id', 'following_users_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Recipes::class, 'comments', 'users_id', 'recipes_id');
    }

    public function access_tokens()
    {
        return $this->hasMany(OAuthAccessTokens::class);
    }

    public function likes()
    {
        return $this->hasMany(Likes::class);
    }

    public function favorite_recipes()
    {
        return $this->hasMany(UsersFavoritesRecipes::class);
    }

    public function ratings()
    {
        return $this->hasMany(Ratings::class);
    }

    public function password_resets()
    {
        return $this->hasMany(PasswordResets::class, 'email', 'email');
    }

    public function email_verifications()
    {
        return $this->hasMany(EmailVerifications::class, 'email', 'email');
    }
}
