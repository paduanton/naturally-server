<?php

namespace App\Services\Interfaces;

use App\Users;
use Carbon\Carbon;
use Laravel\Passport\Token;

interface AuthenticationInterface
{
    public function revokeAllUserActiveTokens(Users $user);
    public function revokeAllUserAccessTokensExceptCurrentOne(Users $user, Token $currentAccessToken);
    public function getUsernamePattern();
    public function generateUsername($user, string $username) : string;
    public function getUserAgeLimitDate();
    public function isEmail($input);
    public function sendPasswordChangingAlert(Users $user);
    public function sendWelcomedMail(Users $user);
    public static function getUniqueHash(int $size = 32);
    public function hashPassword(string $password);
    public function rehashPasswordIfNeeded(string $hashedPassword);
    public function createUsername(string $name);
    public function revokeRefreshToken(string $token);
    public function createRefreshToken(string $accessTokenId, Carbon $accessTokenExpiresAt);
    public function createUserAuthResource(Users $user);
}
