<?php

namespace App\Services;

use App\Users;
use Exception;
use App\SocialNetWorks;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\Interfaces\SocialNetworksProviderInterface;

class SocialNetworksProvider implements SocialNetworksProviderInterface

{
    protected $userRepository;

    public function getUserEntityByAccessToken($provider, $accessToken)
    {
        $user = $this->getUserFromSocialProvider($provider, $accessToken);

        if (!$user) {
            return null;
        }

        return $user;
    }

    protected function getUserFromSocialProvider($provider, $accessToken)
    {
        try {
            $userFromProvider = Socialite::driver($provider)->fields([
                'first_name',
                'middle_name',
                'last_name',
                'email'
            ])->userFromToken($accessToken);
        } catch (Exception $exception) {
            throw new OAuthServerException(
                'Authentication error, invalid access token',
                $errorCode = 400,
                'invalid_request'
            );
        }

        return $this->findOrCreateSocialUser($userFromProvider, $provider);
    }

    protected function findOrCreateSocialUser($providerUser, $provider)
    {
        $socialAccount = SocialNetWorks::where('provider_name', $provider)
            ->where('provider_id', $providerUser->getId())
            ->first();

        if ($socialAccount) {
            return $socialAccount->users;
        }

        $firstName = $providerUser->user['first_name'];
        $middleName = $providerUser->user['middle_name'];
        $lastName = $providerUser->user['last_name'];
        $email = $providerUser->getEmail();
        $username = $providerUser->getUsername();
        $pictureUrl = $providerUser->avatar_original;
        $providerId = $providerUser->getId();
        $profileUrl = $providerUser->profileUrl;

        $socialNetwork = new SocialNetWorks();
        $socialNetwork->provider_name = $provider;
        $socialNetwork->provider_id = $providerId;
        $socialNetwork->username = $username;
        $socialNetwork->profile_url = $profileUrl;
        $socialNetwork->picture_url = $pictureUrl;

        if (!$username) {
            $username = $this->generateUsername($firstName, $lastName);
        }

        $userData = [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'picture_url' => $pictureUrl
        ];

        $user = Users::firstOrCreate(['email' => $email], $userData);
        $user->social_networks()->save($socialNetwork);

        return $user;
    }

    protected function generateUsername($firstName, $lastName)
    {
        $firstName = strtolower($firstName);
        $lastName = strtolower($lastName);
        $username = $firstName . "." . $lastName;

        $username = str_replace(" ", "", $username);
        $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username);

        $user = Users::where('username', $username)->first();

        while ($user) {
            $randomNumber = mt_rand();
            $username = $username . $randomNumber;

            $user = Users::where('username', $username)->first();
        }

        return $username;
    }
}
