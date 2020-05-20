<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialNetworkAccounts extends Model
{
    use SoftDeletes;
    
    protected $table = 'social_network_accounts';

    protected $fillable = [
        'provider_name', 'provider_id', 'username','profile_url', 'picture_url'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }
}
