<?php

namespace App\Services;

use App\Users;
use App\SocialNetWorks;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use Laravel\Passport\Bridge\User as UserEntity;
use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\Interfaces\SocialNetworksProviderInterface;

class SocialNetworksProvider implements SocialNetworksProviderInterface

{
    protected $userRepository;

   
    public function __construct()
    {
    }

   
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
            $userFromProvider = Socialite::driver($provider)->userFromToken($accessToken);
        } catch (\Exception $ex) {
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
        // $socialAccount = SocialNetWorks::where('provider_name', $provider)
        //     ->where('provider_id', $providerUser->getId())
        //     ->first();

        // if ($socialAccount) {
        //     return $socialAccount->users;
        // } else {
            
            $email = $providerUser->getEmail();
            $name = $providerUser->getName();
            $pictureUrl = $providerUser->avatar_original;
            $providerId = $providerUser->getId();

            $socialNetwork = new SocialNetWorks();
            $socialNetwork->provider_name = $provider;
            $socialNetwork->provider_id = $providerId; 
            $socialNetwork->username = $name;
            $socialNetwork->picture_url = $pictureUrl;

            if ($email) {
                $user = Users::where('email', $email)->first();
            }

            if (!$user) {
                $user = Users::create([
                    'name' => $name,
                    'last_name' => $name,
                    'username' => $name,
                    'email' => $email,
                    'picture_url' => $pictureUrl
                ]);
            }
            $user->social_networks()->save($socialNetwork);
            var_dump($user);
            return $user;
        }
    // }

}