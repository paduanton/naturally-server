<?php

namespace App\Services;

use Exception;
use App\Users;
use Carbon\Carbon;
use App\OAuthAccessTokens;
use App\OAuthRefreshTokens;
use Laravel\Passport\Token;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Notifications\WelcomeReminder;
use App\Http\Resources\UserAuthResource;
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

    public function revokeAllUserActiveTokens(Users $user)
    {
        $accessTokens = OAuthAccessTokens::where('user_id', $user->id)->where('revoked', false)->get();

        foreach ($accessTokens as $token) {
            $token->update(['revoked' => true]);
            $token->refresh_token->update(['revoked' => true]);
        }
    }

    public function revokeAllUserAccessTokensExceptCurrentOne(Users $user, Token $currentAccessToken)
    {
        $userAccessTokens = $user->access_tokens;

        foreach ($userAccessTokens as $accessToken) {
            if ($accessToken->revoked) {
                continue;
            } else {
                if ($accessToken->id === $currentAccessToken->id) {
                    continue;
                }

                OAuthAccessTokens::where('id', $accessToken->id)->update(["revoked" => true]);
            }
        }
    }

    public function isEmail($input)
    {
        $match = filter_var($input, FILTER_VALIDATE_EMAIL);

        if ($match) {
            return true;
        }

        return false;
    }

    public function getUsernamePattern()
    {
        /*
            Rules:
            - Only letters and numbers
            - One dot or one underscore in the middle of string
            - Must initiate with A-z or 0-9
            - Min char: 4
            - Max char: 20
            - Must not end with anything other than A-z or 0-9

            Regex: ^[0-9a-zA-Z]+(\.[0-9a-zA-Z]+)?$
        */

        $pattern = "^(?=[^\._]+[\._]?[^\._]+$)[\w\.]{4,20}$";
        $delimiter = "/";

        return $delimiter . $pattern . $delimiter;
    }

    public function getUserAgeLimitDate()
    {
        /*
            Only users 7 years older can access the app
        */

        return  now()->subYears(7)->subDay()->format('Y-m-d');
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

    public function createUsername($name)
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

        $user = Users::where('username', $username)->withTrashed()->first();

        return $this->generateUsername($user, $username);
    }

    /*
    * While there is a user in database with same username or the username length > 60 
    * we keep trying to generate another valid username
    **/
    public function generateUsername($user, string $username): string
    {
        if ($user || strlen($username) > 60) {
            $defaultUsername = $username;

            while (!(!$user && strlen($username) <= 60)) {
                $maxLength = (int) 999999999999999999999999999999; // 30 digits
                $randomNumber = mt_rand(1, $maxLength);

                $username = $defaultUsername . $randomNumber;
                $user = Users::where('username', $username)->withTrashed()->first();
            }
        }

        return $username;
    }

    public function revokeRefreshToken($token)
    {
        $parseToken = explode("?", $token);
        $refreshTokenId = $parseToken[0];
        OAuthRefreshTokens::where('id', $refreshTokenId)->update(["revoked" => true]);
    }

    public function createRefreshToken($accessTokenId, $accessTokenExpiresAt)
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

    public function createUserAuthResource(Users $user)
    {
        $token = $user->createToken('Personal Access Token');
        $accessToken = $token->accessToken;

        $expiresAt = Carbon::parse($token->token->expires_at);
        $createdAt =  Carbon::parse($token->token->created_at);

        $user['auth_resource'] = [
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt,
            'access_token' => $accessToken,
            'created_at' => $createdAt,
            'refresh_token' => $this->createRefreshToken($token->token->id, $expiresAt),
            'remember_token' => $user->remember_token
        ];

        return new UserAuthResource($user);
    }
}
