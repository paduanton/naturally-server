<?php
namespace App\Services\Interfaces;

use App\Users;
use App\RestoredAccounts;

interface RestoreAccountInterface
{
    public function isTokenExpired();
    public function getEncryptedUser();
    public function setToken(string $token);
    public function isUserValid(Users $user);
    public function encryptUser(Users $user);
    public function setEncryptedUser(string $encryptedUser);
    public function sendRestoreEmail(Users $user, RestoredAccounts $restoreAccount);
}
