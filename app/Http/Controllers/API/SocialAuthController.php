<?php

namespace App\Http\Controllers\API;

use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\SocialNetworksProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

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
        $providerAccessToken = $request['access_token'];
        
        if (!$provider) {
            throw OAuthServerException::invalidRequest('provider');
        }

        if (!$providerAccessToken) {
            throw OAuthServerException::invalidRequest('access_token');
        }

        if (!$this->isProviderSupported($provider)) {
            throw OAuthServerException::invalidRequest('provider', 'Invalid provider');
        }

        try {
            $user = $this->socialProvider->getUserEntityByAccessToken($provider, $providerAccessToken);
            $accessToken = $this->generateToken($user);

            return response()->json($accessToken);
        } catch (Exception $exception) {
            throw OAuthServerException::invalidCredentials();
        }

    }

    protected function isProviderSupported($provider)
    {
        return in_array($provider, ['facebook', 'google', 'twitter']);
    }

    protected function generateToken($user)
    {
        $token = $user->createToken('Personal Access Token');
        $accessToken = $token->accessToken;

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $token->token->expires_at
            )->toDateTimeString()
        ];
    }
}
