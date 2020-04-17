<?php

namespace App\Http\Controllers\API;

use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\SocialNetworksProvider;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
        $this->validate($request, [
            'access_token' => 'required|string',
            'remember_me' => 'nullable|boolean',
            'provider' => [
                'required',
                'string',
                Rule::in(['facebook', 'twitter'])
            ]
        ]);

        $provider = $request['provider'];
        $providerAccessToken = $request['access_token'];
        $remember = $request['remember_me'];
        
        try {
            $user = $this->socialProvider->getUserEntityByAccessToken($provider, $providerAccessToken);
            $accessToken = $this->generateToken($user);

            Auth::login($user, $remember);

            return response()->json($accessToken);
        } catch (Exception $exception) {
            throw OAuthServerException::invalidCredentials($exception);
        }
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
