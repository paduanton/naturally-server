<?php

namespace App\Services\Interfaces;

interface SocialNetworksProviderInterface
{
    
    public function getUserEntityByAccessToken($provider, $accessToken);
}