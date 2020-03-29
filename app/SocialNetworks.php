<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SocialNetWorks extends Model
{
    protected $table = 'social_networks';

    public function users()
    {
        return $this->belongsTo(Users::class);
    }
}
