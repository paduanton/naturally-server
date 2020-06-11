<?php

namespace App\Services;

use Exception;
use App\Users;
use Carbon\Carbon;
use App\RestoredAccounts;
use App\Notifications\RestoreAccount;
use Illuminate\Support\Facades\Hash;
use App\Services\Interfaces\RestoreAccountInterface;

class RestoreAccountService implements RestoreAccountInterface
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
        $restoreAccount = RestoredAccounts::where('token', $this->token)->first();

        if (Carbon::parse($restoreAccount->expires_at)->isPast()) {
            return true;
        }

        return false;
    }

    public function sendRestoreEmail(Users $user, RestoredAccounts $restoreAccount)
    {
        try {
            $user->notify(new RestoreAccount($restoreAccount));
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}
