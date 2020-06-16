<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Phones extends Model
{
    use SoftDeletes;

    protected $table = 'phones';

    protected $fillable = [
        'users_id', 'main', 'label',
         'country_code', 'number'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }

}
