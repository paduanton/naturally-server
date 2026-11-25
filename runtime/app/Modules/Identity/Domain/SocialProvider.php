<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain;

enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case X = 'x';
}
