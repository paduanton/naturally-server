<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialNetWorks extends Model
{
    use SoftDeletes;
    
    protected $table = 'social_networks';

    protected $fillable = [
        'provider_name', 'provider_id', 'username','profile_url', 'picture_url'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }
}
