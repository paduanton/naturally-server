<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PDFDownloads extends Model
{
    protected $fillable = [
        'recipes_id', 'users_id', 'ip', 'browser'
    ];

    public function users()
    {
        return $this->belongsTo(Users::class);
    }

    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }
}
