<?php

namespace App\Http\Controllers\API;

use App\Services\AuthenticationService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\OAuthRefreshTokens;
use App\OAuthAccessTokens;
use Carbon\Carbon;
use App\Users;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthenticationService $auth)
    {
        $this->authService = $auth;
    }

    public function signup(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed|string|min:6',
            'birthday' => 'nullable|date',
            'remember_me' => 'nullable|boolean',
        ]);

        $remember = $request['remember_me'];
        $request['username'] = $this->authService->generateUsername($request['name']);
        $request['password'] = $this->authService->hashPassword($request['password']);

        $user = Users::create($request->all());
        Auth::login($user, $remember);

        if ($user) {
            $user['authentication'] = $this->authService->generateAccessToken($user);
            return response()->json($user, 201);
        }

        return response()->json([
            'message' => "couldn't sign user up"
        ], 400);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'email|required_without:username',
            'username' => 'string|required_without:email',
            'password' => 'required|string',
            'remember_me' => 'nullable|boolean'
        ]);

        $remember = $request['remember_me'];
        $login = isset($request['username']) ? 'username' : 'email';

        $credentials = request([$login, 'password']);
        if (!Auth::attempt($credentials, $remember)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => "Invalid credentials",
            ], 401);
        }

        $user = $request->user();
        $user->update(['password' => $this->authService->rehashPasswordIfNeeded($user->password)]);
        
        $user['authentication'] = $this->authService->generateAccessToken($user);

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $accessToken = $request->user()->token();
        $accessTokenId = $request->user()->token()->id;
        $accessTokenModel = OAuthAccessTokens::findOrFail($accessTokenId);

        $revokeAccessToken = $accessToken->revoke();

        if ($revokeAccessToken) {
            $this->authService->revokeRefreshToken($accessTokenModel->refresh_token->token);

            return response()->json([
                'message' => 'Logout successfully'
            ], 200);
        }

        return response()->json([
            'message' => "couldn't logout"
        ], 409);
    }

    public function refreshToken(Request $request)
    {
        $this->validate($request, [
            'refresh_token' => 'required|string|exists:App\OAuthRefreshTokens,token'
        ]);

        $parseToken = explode("?", $request['refresh_token']);
        $refreshTokenId = $parseToken[0];
        $authenticatedUser = Auth::user();

        $refreshToken = OAuthRefreshTokens::find($refreshTokenId);
        $accessToken = $refreshToken->access_token;
        $user = $accessToken->user;

        $refreshTokenExpiration = Carbon::parse($refreshToken->expires_at);
        $now = Carbon::now();

        if ($refreshToken->revoked) {
            return response()->json([
                'error' => 'invalid token',
                'message' => "refresh token has been revoked already"
            ], 400);
        }

        if ($now->greaterThan($refreshTokenExpiration)) {
            $this->authService->revokeRefreshToken($request['refresh_token']);

            return response()->json([
                'error' => 'invalid token',
                'message' => "refresh token has been expired"
            ], 400);
        }

        if ($user->id !== $authenticatedUser->getAuthIdentifier()) {
            return response()->json([
                'error' => 'invalid token',
                'message' => "it is not possible to refresh a token of another user"
            ], 400);
        }

        if ($request->user()->token()->id != $accessToken->id) {
            return response()->json([
                'error' => 'invalid token',
                'message' => "access token does not match the referenced refresh token"
            ], 400);
        }

        $request->user()->token()->revoke();
        $this->authService->revokeRefreshToken($request['refresh_token']);
        return $this->authService->generateAccessToken($authenticatedUser);
    }
}
