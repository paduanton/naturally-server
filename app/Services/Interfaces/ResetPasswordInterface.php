<?php

namespace App\Services\Interfaces;

use App\Users;

interface ResetPasswordInterface
{
    public function setToken(string $token);
    public function isTokenExpired();
    public function sendResetLinkEmail(Users $user);
    public function sendSuccessfullyResetedEmail(Users $user);
}