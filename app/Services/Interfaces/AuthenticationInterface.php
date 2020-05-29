<?php

namespace App\Services\Interfaces;

use App\Users;
use Carbon\Carbon;

interface AuthenticationInterface
{

    public function sendWelcomeMail(Users $user);
    public static function getUniqueHash(int $size = 32);
    public function hashPassword(string $password);
    public function rehashPasswordIfNeeded(string $hashedPassword);
    public function getRefreshTokenInfo(string $token);
    public function generateUsername(string $name);
    public function revokeRefreshToken(string $token);
    public function generateRefreshToken(string $accessTokenId, Carbon $accessTokenExpiresAt);
    public function generateAccessToken(Users $user);
}
