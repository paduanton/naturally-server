<?php

namespace App\Services\Interfaces;

use App\Users;

interface AuthenticationInterface
{

    public static function getUniqueHash(int $size = 32);
    public function getRefreshTokenInfo(string $token);
    public function generateUsername(string $name);
    public function revokeRefreshToken(string $token);
    public function generateRefreshToken(string $accessTokenId, string $accessTokenExpiresAt);
    public function generateAccessToken(Users $user);
}
