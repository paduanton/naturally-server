<?php

namespace App\Services\Interfaces;

use App\Users;
use Carbon\Carbon;
use App\EmailVerifications;

interface VerifyEmailInterface
{
    public function isTokenExpired();
    public function getEncryptedUser();
    public function setToken(string $token);
    public function isUserValid(Users $user);
    public function encryptUser(Users $user);
    public function setEncryptedUser(string $encryptedUser);
    public function sendVerifyEmail(Users $user, EmailVerifications $emailVerification);
}
