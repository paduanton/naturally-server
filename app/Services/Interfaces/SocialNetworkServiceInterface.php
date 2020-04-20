<?php

namespace App\Services\Interfaces;

interface SocialNetworkServiceInterface
{
    public function getUserEntityByAccessToken($provider, $accessToken);
    public function getUserEntityByAccessTokenAndSecret($provider, $accessToken, $accessTokenSecret);
    public function getUserFromSocialProvider($provider, $accessToken, $accessTokenSecret);
    public function isOAuth2ProviderSupported($provider);
    public function isOAuth1ProviderSupported($provider);
}