<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OAuthRefreshTokens extends Model
{
    protected $table = 'oauth_refresh_tokens';

    protected $fillable = [
        'id', 'access_token_id', 'token', 'revoked', 'expires_at'
    ];

    protected $casts = [
        'id' => 'string'
    ];
    
    public $timestamps = false;
    
    public function access_token()
    {
        return $this->belongsTo(OAuthAccessTokens::class);
    }
}
