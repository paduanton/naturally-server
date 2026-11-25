<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Infrastructure\Database\Factories\PhoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Phone extends Model
{
    /** @use HasFactory<PhoneFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'phones';
    protected $guarded = ['*'];
    protected $hidden = ['number', 'primary_slot'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'main' => 'boolean', 'number' => \App\Modules\Identity\Infrastructure\Database\Casts\PhoneNumberCast::class];
    }

    protected static function newFactory(): PhoneFactory
    {
        return PhoneFactory::new();
    }

}
