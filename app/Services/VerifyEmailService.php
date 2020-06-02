<?php

namespace App\Services;

use Exception;
use App\Users;
use Carbon\Carbon;
use App\EmailVerifications;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use App\Services\Interfaces\VerifyEmailInterface;

class VerifyEmailService implements VerifyEmailInterface
{
    protected $token, $encryptedUser;

    public function __construct()
    {
        //
    }

    public function getEncryptedUser()
    {
        return $this->encryptedUser;
    }

    public function setEncryptedUser($encryptedUser)
    {
        $this->encryptedUser = $encryptedUser;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function encryptUser(Users $user)
    {
        $email = $user->email;
        $name = $user->name;
        $username = $user->username;

        $concatData = $email . $name . $username;
        $encryption = Hash::make($concatData);

        $this->setEncryptedUser($encryption);
    }

    public function isUserValid(Users $user)
    {
        $encryptedUser = $this->getEncryptedUser();

        $email = $user->email;
        $name = $user->name;
        $username = $user->username;

        $concatData = $email . $name . $username;
        if (Hash::check($concatData, $encryptedUser)) {
            return true;
        }

        return false;
    }

    public function isTokenExpired()
    {
        $emailVerification = EmailVerifications::where('token', $this->token)->first();

        if (Carbon::parse($emailVerification->expires_at)->isPast()) {
            return true;
        }

        return false;
    }

    public function sendVerifyEmail(Users $user, EmailVerifications $emailVerification)
    {
        try {
            $user->notify(new VerifyEmail($emailVerification));
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}
