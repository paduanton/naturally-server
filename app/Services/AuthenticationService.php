<?php

namespace App\Services;

use Exception;
use App\Users;
use Carbon\Carbon;
use App\OAuthRefreshTokens;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Notifications\WelcomeReminder;
use App\Notifications\PasswordChangingAlert;
use App\Services\Interfaces\AuthenticationInterface;

class AuthenticationService implements AuthenticationInterface
{
    public function __construct()
    {
        //    
    }

    public function hashPassword($password)
    {
        return Hash::make($password);
    }

    public function isEmail($input)
    {
        $match = filter_var($input, FILTER_VALIDATE_EMAIL);

        if ($match) {
            return true;
        }

        return false;
    }

    public function sendPasswordChangingAlert(Users $user)
    {
        try {
            $user->notify(new PasswordChangingAlert());
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    public function sendWelcomedMail(Users $user)
    {
        try {
            $user->notify(new WelcomeReminder($user));
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    public function rehashPasswordIfNeeded($hashedPassword)
    {
        if (Hash::needsRehash($hashedPassword)) {
            $hashedPassword = $this->hashPassword($hashedPassword);
        }

        return $hashedPassword;
    }

    public static function getUniqueHash(int $size = 32)
    {
        return bin2hex(openssl_random_pseudo_bytes($size));
    }


    public function getRefreshTokenInfo($token)
    {
        $parseToken = explode("?", $token);
        $refreshTokenId = $parseToken[0];

        $refreshToken = OAuthRefreshTokens::findOrFail($refreshTokenId);
        return response()->json($refreshToken);
    }

    public function generateUsername($name)
    {
        $firstName = strtok($name, ' ');
        $lastName = strrchr($name, ' ');

        if (!$lastName) {
            $username = $firstName;
        } else {
            $username = $firstName . "." . $lastName;
        }

        $username = str_replace(" ", "", $username);
        $username = Str::ascii($username);
        $username = strtolower($username);
        $username = preg_replace("/[^A-Za-z.]/", '', $username);

        if (!$username) {
            $username = 'user' . mt_rand();
        }

        $user = Users::where('username', $username)->first();

        while ($user) {
            $randomNumber = mt_rand();
            $username = $username . $randomNumber;

            $user = Users::where('username', $username)->first();
        }

        return $username;
    }

    public function revokeRefreshToken($token)
    {
        $parseToken = explode("?", $token);
        $refreshTokenId = $parseToken[0];
        OAuthRefreshTokens::where('id', $refreshTokenId)->update(["revoked" => true]);
    }

    public function generateRefreshToken($accessTokenId, $accessTokenExpiresAt)
    {
        try {
            $uniqueHash = $this->getUniqueHash();

            $refreshToken = new OAuthRefreshTokens();
            $refreshToken->id = $uniqueHash;
            $refreshToken->access_token_id = $accessTokenId;
            $refreshToken->token = $uniqueHash . '?' . Str::random(690);
            $refreshToken->revoked = false;
            $refreshToken->expires_at = $accessTokenExpiresAt->addMonth(1);

            $findById = OAuthRefreshTokens::find($refreshToken->id);

            while ($findById && strlen($uniqueHash) > 767) {
                $uniqueHash = $this->getUniqueHash();

                $refreshToken->id = $uniqueHash;
                $refreshToken->token = $uniqueHash . '?' . Str::random(690);

                $findById = OAuthRefreshTokens::find($refreshToken->id);
            }

            $refreshToken->save();
        } catch (Exception $exception) {
            return false;
        }

        return $refreshToken->token;
    }

    public function generateUserAuthResource(Users $user)
    {
        $token = $user->createToken('Personal Access Token');
        $accessToken = $token->accessToken;
        $expiresAt = Carbon::parse($token->token->expires_at);

        return [
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->toDateTimeString(),
            'access_token' => $accessToken,
            'refresh_token' => $this->generateRefreshToken($token->token->id, $expiresAt),
            'remember_token' => $user->remember_token
        ];
    }
}
