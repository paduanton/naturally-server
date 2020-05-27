<?php

namespace App\Services\Interfaces;

use App\Users;

interface ResetPasswordInterface
{
    public function isTokenExpired(string $token);
    public function sendResetLinkEmail(Users $user, string $token);
    public function sendSuccessfullyResetedEmail(Users $user);
}