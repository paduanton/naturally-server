<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PDFDownloads extends Model
{
    protected $table = 'pdf_downloads';

    protected $fillable = [
        'recipes_id', 'users_id', 'ip', 
        'user_agent', 'hostname', 'created_at'
    ];

    public $timestamps = false;
    
    public function users()
    {
        return $this->belongsTo(Users::class);
    }

    public function recipes()
    {
        return $this->belongsTo(Recipes::class);
    }
}
