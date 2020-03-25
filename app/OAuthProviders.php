<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OAuthProviders extends Model
{
    protected $table = 'oauth_providers';

    public function users()
    {
        return $this->belongsTo('App\Users');
    }
}
