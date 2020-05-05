<?php

namespace App\Http\Controllers\API;

use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\SocialNetworkAccountService;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class SocialAuthController extends Controller
{

    protected $socialProvider;
    protected $frontendURL;
    
    public function __construct(SocialNetworkAccountService $social)
    {
        $this->socialProvider = $social;
        $this->frontendURL = config('app.frontend_url');
    }

    public function authenticate(Request $request)
    {
        $this->validate($request, [
            'access_token' => 'required|string',
            'remember_me' => 'nullable|boolean',
            'provider' => [
                'required',
                'string',
                Rule::in(['facebook', 'twitter', 'google'])
            ],
            'access_token_secret' => 'string|required_if:provider,twitter'
        ]);

        $provider = $request['provider'];
        $remember = $request['remember_me'];
        $providerAccessToken = $request['access_token'];
        $providerAccessTokenSecret = isset($request['access_token_secret']) && $provider === 'twitter' ? $request['access_token_secret'] : null;

        try {
            $user = $this->socialProvider->getUserFromSocialProvider($provider, $providerAccessToken, $providerAccessTokenSecret);
            $accessToken = $this->generateAccessToken($user);

            Auth::login($user, $remember);
        } catch (OAuthServerException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw $exception;
        }

        return response()->json($accessToken);
    }

    public function handleProviderCallback($provider)
    {
        if (!$this->socialProvider->isOAuth1ProviderSupported($provider)) {
            throw OAuthServerException::invalidCredentials();
        }

        try {
            $userFromProvider = Socialite::driver($provider)->user();

            $providerAccessToken = $userFromProvider->token;
            $providerAccessTokenSecret = $userFromProvider->tokenSecret;
        } catch (Exception $exception) {
            throw $exception;
        } finally {
            return redirect()->away($this->frontendURL . "/provider/{$provider}/token/{$providerAccessToken}/secret/{$providerAccessTokenSecret}/callback"); // frontend callback
            /*
            return response()->json([
                'provider' => $provider,
                'access_token' => $providerAccessToken,
                'access_token_secret' => $providerAccessTokenSecret
            ]);
            */
        }
    }

    public function redirectToProvider($provider)
    {
        if (!$this->socialProvider->isOAuth1ProviderSupported($provider)) {
            return response()->json([
                'error' => 'invalid provider',
                'message' => "the social provider {$provider} is not supported",
            ], 400);
        }

        return Socialite::driver($provider)->redirect();
    }

    protected function generateAccessToken($user)
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
