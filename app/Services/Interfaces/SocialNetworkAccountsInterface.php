<?php

namespace App\Services\Interfaces;

interface SocialNetworkAccountsInterface
{
    public function getUserEntityByAccessToken(string $accessToken);
    public function getUserEntityByAccessTokenAndSecret(string $accessToken, string $accessTokenSecret);
    public function getUserFromSocialProvider();
    public function isOAuth2ProviderSupported(string $provider);
    public function isOAuth1ProviderSupported(string $provider);
}