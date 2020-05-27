<?php

namespace App\Services;

use Exception;
use App\Users;
use Carbon\Carbon;
use App\PasswordResets;
use App\Notifications\PasswordResetSuccess;
use App\Notifications\PasswordResetRequest;
use App\Services\Interfaces\ResetPasswordInterface;

class ResetPasswordService implements ResetPasswordInterface
{
    public function isTokenExpired($token)
    {
        $passwordReset = PasswordResets::where('token', $token)->first();
        
        if (Carbon::parse($passwordReset->expires_at)->isPast()) {
            return true;
        }

        return false;
    }

    public function sendResetLinkEmail(Users $user, $token)
    {
        try {
            $user->notify(new PasswordResetRequest($token));
        } catch (Exception $exception) {
            return false;
        } finally {
            return true;
        }
    }

    public function sendSuccessfullyResetedEmail(Users $user)
    {
        try {
            $user->notify(new PasswordResetSuccess());
        } catch (Exception $exception) {
            return false;
        } finally {
            return true;
        }
    }
}
