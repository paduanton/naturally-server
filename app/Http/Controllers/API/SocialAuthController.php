<?php

namespace App\Http\Controllers\API;

use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\SocialNetworksProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SocialNetWorks;
use Exception;
use App\Users;

class SocialAuthController extends Controller
{

    protected $socialProvider;

   
    public function __construct(SocialNetworksProvider $social)
    {
        $this->socialProvider = $social;
    }

    public function authenticate(Request $request)
    {
        
        $provider = $request['provider'];
        $accessToken = $request['access_token'];
        
        try {
            $socialResponse = $this->socialProvider->getUserEntityByAccessToken($provider, $accessToken);

            return response($socialResponse);

        } catch (Exception $exception) {}

        // $user = $this->socialUserProvider->getUserEntityByAccessToken(
        //     $provider,
        //     $accessToken
        // );

        // if ($user instanceof UserEntityInterface === false) {
        //     $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

        //     throw OAuthServerException::invalidCredentials();
        // }

        // if (is_null($provider)) {
        //     throw OAuthServerException::invalidRequest('provider');
        // }

        // if (! $this->isProviderSupported($provider)) {
        //     throw OAuthServerException::invalidRequest('provider', 'Invalid provider');
        // }

        // $accessToken = $this->getRequestParameter('access_token', $request);

        // if (is_null($accessToken)) {
        //     throw OAuthServerException::invalidRequest('access_token');
        // }
    }

    protected function isProviderSupported($provider)
    {
        return in_array($provider, config('auth.social.providers'));
    }
}
