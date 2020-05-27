<?php

namespace App\Services;

use Exception;
use App\Users;
use App\Notifications\PasswordResetRequest;
use App\Services\Interfaces\ResetPasswordInterface;

class ResetPasswordService implements ResetPasswordInterface
{
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function isTokenExpired()
    {
    }

    public function notificateUser(Users $user)
    {
        try {
            $user->notify(new PasswordResetRequest($this->token));
        } catch (Exception $exception) {
            return false;
        } finally {
            return true;
        }
    }
}
