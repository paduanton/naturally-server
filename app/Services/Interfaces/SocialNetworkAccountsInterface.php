<?php

namespace App\Services\Interfaces;

interface SocialNetworkAccountsInterface
{
    public function getUserEntityByAccessToken($accessToken);
    public function getUserEntityByAccessTokenAndSecret($accessToken, $accessTokenSecret);
    public function getUserFromSocialProvider();
    public function isOAuth2ProviderSupported($provider);
    public function isOAuth1ProviderSupported($provider);
}