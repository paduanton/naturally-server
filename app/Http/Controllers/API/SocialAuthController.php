<?php

namespace App\Http\Controllers\API;

use League\OAuth2\Server\Exception\OAuthServerException;
use App\Services\SocialNetworkAccountService;
use App\Http\Controllers\API\AuthController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Exception;

class SocialAuthController extends Controller
{

    protected $socialProvider;
    protected $frontendURL;
    protected $authController;

    public function __construct(SocialNetworkAccountService $social, AuthController $authController)
    {
        $this->socialAuthService = $social;
        $this->authController = $authController;
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

        $remember = $request['remember_me'];
        $provider = $request['provider'];
        $providerAccessToken = $request['access_token'];
        $providerAccessTokenSecret = isset($request['access_token_secret']) && $provider === 'twitter' ? $request['access_token_secret'] : null;

        $this->socialAuthService->setProvider($provider);
        $this->socialAuthService->setAccessToken($providerAccessToken);

        if ($providerAccessTokenSecret) {
            $this->socialAuthService->setAccessTokenSecret($providerAccessTokenSecret);
        }

        try {
            $user = $this->socialAuthService->getUserFromSocialProvider();
            Auth::login($user, $remember);
            $accessToken = $this->authController->generateAccessToken($user);
        } catch (OAuthServerException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw $exception;
        }

        return response()->json($accessToken);
    }

    public function handleProviderCallback($provider)
    {
        if (!$this->socialAuthService->isOAuth1ProviderSupported($provider)) {
            throw OAuthServerException::invalidCredentials();
        }

        try {
            $userFromProvider = Socialite::driver($provider)->user();

            $providerAccessToken = $userFromProvider->token;
            $providerAccessTokenSecret = $userFromProvider->tokenSecret;
        } catch (Exception $exception) {
            throw $exception;
        }

        // frontend callback

        return redirect()->away($this->frontendURL . "/provider/{$provider}/callback?token={$providerAccessToken}&secret={$providerAccessTokenSecret}");

        /*
        return response()->json([
            'provider' => $provider,
            'access_token' => $providerAccessToken,
            'access_token_secret' => $providerAccessTokenSecret
        ]);
        */
    }

    public function redirectToProvider($provider)
    {
        if (!$this->socialAuthService->isOAuth1ProviderSupported($provider)) {
            return response()->json([
                'error' => 'invalid provider',
                'message' => "the social provider '{$provider}' is not supported",
            ], 400);
        }

        return Socialite::driver($provider)->redirect();
    }
}
