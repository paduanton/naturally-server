<?php

declare(strict_types=1);

namespace App\Modules\Community\Infrastructure\Persistence;

use App\Modules\Community\Infrastructure\Database\Factories\FollowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


final class Follow extends Model
{
    /** @use HasFactory<FollowFactory> */
    use HasFactory;


    protected $table = 'follows';
    protected $guarded = ['*'];
    protected $hidden = [];

    protected function casts(): array
    {
        return ['follower_id' => 'integer', 'followed_id' => 'integer'];
    }

    protected static function newFactory(): FollowFactory
    {
        return FollowFactory::new();
    }

}
