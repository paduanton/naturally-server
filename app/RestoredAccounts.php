<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestoredAccounts extends Model
{   
    use SoftDeletes;
    
    protected $table = 'restored_accounts';

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
