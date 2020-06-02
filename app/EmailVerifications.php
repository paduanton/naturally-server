<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailVerifications extends Model
{   
    use SoftDeletes;
    
    protected $table = 'email_verifications';

    protected $fillable = [
        'email', 'token', 'signature', 'done', 'expires_at'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $dates = ['expires_at'];

    public function users()
    {
        return $this->belongsTo(Users::class, 'email', 'email');
    }
    
}
