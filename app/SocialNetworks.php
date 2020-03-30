<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SocialNetWorks extends Model
{
    protected $table = 'social_networks';

    protected $fillable = [
        'provider_name', 'provider_id', 'nickname','profile_url', 'picture_url'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }
}
