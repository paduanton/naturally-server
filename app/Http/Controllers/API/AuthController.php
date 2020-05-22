<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\OAuthRefreshTokens;
use App\OAuthAccessTokens;
use Carbon\Carbon;
use App\Users;
use Exception;

class AuthController extends Controller
{

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
        $request['username'] = $this->generateUsername($request['name']);
        $request['password'] = Hash::make($request['password']);

        $user = Users::create($request->all());
        Auth::login($user, $remember);

        if ($user) {
            $user['authentication'] = $this->generateAccessToken($user);
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
        $user['authentication'] = $this->generateAccessToken($user);

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $accessToken = $request->user()->token();
        $accessTokenId = $request->user()->token()->id;
        $accessTokenModel = OAuthAccessTokens::findOrFail($accessTokenId);

        $revokeAccessToken = $accessToken->revoke();

        if ($revokeAccessToken) {
            $this->revokeRefreshToken($accessTokenModel->refresh_token->token);

            return response()->json([
                'message' => 'Logout successfully'
            ], 200);
        }

        return response()->json([
            'message' => "couldn't logout"
        ], 409);
    }

    public function getRefreshTokenInfo($token)
    {
        $parseToken = explode("?", $token);
        $refreshTokenId = $parseToken[0];

        $refreshToken = OAuthRefreshTokens::findOrFail($refreshTokenId);
        return response()->json($refreshToken);
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
            $this->revokeRefreshToken($request['refresh_token']);

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
        $this->revokeRefreshToken($request['refresh_token']);
        return $this->generateAccessToken($authenticatedUser);
    }

    protected function revokeRefreshToken($token)
    {
        $parseToken = explode("?", $token);
        $refreshTokenId = $parseToken[0];
        OAuthRefreshTokens::where('id', $refreshTokenId)->update(["revoked" => true]);
    }

    protected function generateRefreshToken($accessTokenId, $accessTokenExpiresAt)
    {
        try {
            $uniqueHash = $this->getUniqueHash();

            $refreshToken = new OAuthRefreshTokens();
            $refreshToken->id = $uniqueHash;
            $refreshToken->access_token_id = $accessTokenId;
            $refreshToken->token = $uniqueHash . '?' . Str::random(690);
            $refreshToken->revoked = false;
            $refreshToken->expires_at = $accessTokenExpiresAt->addMonth(1);

            $findById = OAuthRefreshTokens::find($refreshToken->id);

            while ($findById && strlen($uniqueHash) > 767) {
                $uniqueHash = $this->getUniqueHash();

                $refreshToken->id = $uniqueHash;
                $refreshToken->token = $uniqueHash . '?' . Str::random(690);

                $findById = OAuthRefreshTokens::find($refreshToken->id);
            }

            $refreshToken->save();
        } catch (Exception $exception) {
            return false;
        } finally {
            return $refreshToken->token;
        }
    }

    public function generateAccessToken($user)
    {
        $token = $user->createToken('Personal Access Token');
        $accessToken = $token->accessToken;
        $expiresAt = Carbon::parse($token->token->expires_at);

        return [
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->toDateTimeString(),
            'access_token' => $accessToken,
            'refresh_token' => $this->generateRefreshToken($token->token->id, $expiresAt),
        ];
    }

    protected static function getUniqueHash(int $size = 32)
    {
        return bin2hex(openssl_random_pseudo_bytes($size));
    }

    protected function generateUsername($name)
    {
        $firstName = strtok($name, ' ');
        $lastName = strrchr($name, ' ');

        if (!$lastName) {
            $username = $firstName;
        } else {
            $username = $firstName . "." . $lastName;
        }

        $username = str_replace(" ", "", $username);
        $username = Str::ascii($username);
        $username = strtolower($username);
        $username = preg_replace("/[^A-Za-z.]/", '', $username);

        if (!$username) {
            $username = 'user' . mt_rand();
        }

        $user = Users::where('username', $username)->first();

        while ($user) {
            $randomNumber = mt_rand();
            $username = $username . $randomNumber;

            $user = Users::where('username', $username)->first();
        }

        return $username;
    }
}
