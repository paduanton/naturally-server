<?php

namespace App\Services\Interfaces;

use App\Users;

interface ResetPasswordInterface
{
    public function isTokenExpired();
    public function setResetPasswordToken(string $token);
    public function sendResetLinkEmail(Users $user);
    public function sendSuccessfullyResetedEmail(Users $user);
}