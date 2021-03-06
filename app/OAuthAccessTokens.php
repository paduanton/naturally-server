<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OAuthAccessTokens extends Model
{
    protected $table = 'oauth_access_tokens';

    protected $fillable = [
        'user_id', 'client_id', 'scopes', 'revoked', 'expires_at'
    ];

    protected $casts = [
        'id' => 'string'
    ];

    public function refresh_token()
    {
        return $this->hasOne(OAuthRefreshTokens::class, 'access_token_id');
    }

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
