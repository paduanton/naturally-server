<?php

namespace App\Services;

use App\Services\Interfaces\AuthenticationInterface;

class AuthenticationService implements AuthenticationInterface
{
    public static function getUniqueHash(int $size = 32)
    {
        return bin2hex(openssl_random_pseudo_bytes($size));
    }
}
