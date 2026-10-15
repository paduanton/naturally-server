<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Infrastructure\Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


final class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;


    protected $table = 'social_accounts';
    protected $guarded = ['*'];
    protected $hidden = ['provider_user_id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'provider' => \App\Modules\Identity\Domain\SocialProvider::class];
    }

    protected static function newFactory(): SocialAccountFactory
    {
        return SocialAccountFactory::new();
    }

}
