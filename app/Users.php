<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Users extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, SoftCascadeTrait;

    protected $table = 'users';

    protected $fillable = [
        'name', 'username', 'email', 'password', 'birthday', 'email_verified_at'
    ];

    protected $hidden = [
        'password', 'remember_token', 'deleted_at', 'pivot'
    ];

    protected $casts = [
        'email_verified_at'
    ];

    protected $softCascade = [
        'recipes', 'social_network_accounts', 'images', 'follower_relationship', 'following_relationship', 'comments', 'likes',
        'favorite_recipes', 'ratings', 'password_resets', 'email_verifications', 'restored_accounts', 'phones'
    ];

    /*
    *   Model relationships
    **/

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

    public function following_relationship()
    {
        return $this->hasMany(Followers::class, 'following_users_id');
    }

    public function follower_relationship()
    {
        return $this->hasMany(Followers::class, 'users_id');
    }

    public function followers()
    {
        return $this->belongsToMany(Users::class, 'followers', 'following_users_id', 'users_id')->wherePivot('unfollowed_at', null);
    }

    public function following()
    {
        return $this->belongsToMany(Users::class, 'followers', 'users_id', 'following_users_id')->wherePivot('unfollowed_at', null);
    }

    public function comments()
    {
        return $this->hasMany(Comments::class);
    }

    public function access_tokens()
    {
        return $this->hasMany(OAuthAccessTokens::class, 'user_id');
    }

    public function likes()
    {
        return $this->hasMany(Likes::class);
    }

    public function favorite_recipes()
    {
        return $this->hasMany(UsersFavoriteRecipes::class);
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

    public function restored_accounts()
    {
        return $this->hasMany(RestoredAccounts::class, 'email', 'email');
    }

    public function phones()
    {
        return $this->hasMany(Phones::class);
    }

    public function reports()
    {
        return $this->hasMany(Reports::class);
    }

    public function users_recipes_images()
    {
        return $this->hasManyThrough(RecipesImages::class, Recipes::class);
    }

    public function users_ratings_images()
    {
        return $this->hasManyThrough(RatingsImages::class, Ratings::class);
    }

    /*
    *   Model queries
    **/

    public function thumbnail()
    {
        Users::findOrFail($this->getKey());
        $userThumbnail = ProfileImages::where('users_id', $this->getKey())->where('thumbnail', true)->first();

        if (!$userThumbnail) {
            $userThumbnail = new ProfileImages();
            $defaultUserPicture = config('app.default_user_picture');

            $userThumbnail->id = 0;
            $userThumbnail->users_id = 0;
            $userThumbnail->title = "Default John Doe picture";
            $userThumbnail->alt = "Main picture of user: {$this->name}";
            $userThumbnail->thumbnail = true;
            $userThumbnail->picture_url = $defaultUserPicture;
            $userThumbnail->filename = basename($defaultUserPicture);
            $userThumbnail->path = "uploads/users/images/default-picture.png";
            $userThumbnail->mime = "image/png";
            $userThumbnail->original_filename = basename($defaultUserPicture);
            $userThumbnail->original_extension = "png";
            $userThumbnail->created_at = now();
            $userThumbnail->updated_at = now();
        }

        return $userThumbnail;
    }

    public static function getAuthorAccount(): Users
    {
        $AUTHOR_EMAIL = config('app.author');

        if (!$AUTHOR_EMAIL) {
            $author = Users::find(1);
        } else {
            $author = Users::where('email', $AUTHOR_EMAIL)->first();
        }

        return $author;
    }
}
