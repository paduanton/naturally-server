<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\OAuthRefreshTokens;
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
            'password' => 'required|confirmed|string',
            'birthday' => 'nullable|date',
            'remember_me' => 'nullable|boolean',
        ]);

        $remember = $request['remember_me'];
        $request['username'] = $this->generateUsername($request['name']);
        $request['password'] = Hash::make($request['password']);
        $user = Users::create($request->all());

        Auth::login($user, $remember);

        if ($user) {
            return response()->json($this->generateAccessToken($user), 201);
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
        return response()->json($this->generateAccessToken($user));
    }

    public function logout(Request $request)
    {
        $token = $request->user()->token()->revoke();

        if ($token) {
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
        $user = $accessToken->users;

        $refreshTokenExpiration = Carbon::parse($refreshToken->expires_at);
        $now = Carbon::now();

        if($now->greaterThan($refreshTokenExpiration)) {
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
        return $this->generateAccessToken($authenticatedUser);
    }

    protected function generateRefreshToken($tokenId, $accessTokenExpiresAt)
    {
        try {
            $uniqueHash = md5(mt_rand() . microtime() . uniqid());

            $refreshToken = new OAuthRefreshTokens();
            $refreshToken->id = $uniqueHash;
            $refreshToken->access_token_id = $tokenId;
            $refreshToken->token = $uniqueHash . '?' . Str::uuid() . Str::random(690);
            $refreshToken->revoked = false;
            $refreshToken->expires_at = $accessTokenExpiresAt->addMonth(1);

            $findByToken = OAuthRefreshTokens::where('token', $refreshToken->token)->first();
            $findById = OAuthRefreshTokens::find($refreshToken->id);

            while ($findById || $findByToken) {
                $uniqueHash = md5(mt_rand() . microtime() . uniqid());

                $refreshToken->id = $uniqueHash;
                $refreshToken->token = $uniqueHash . '?' . Str::uuid() . Str::random(690);

                $findById = OAuthRefreshTokens::find($refreshToken->id);
                $findByToken = OAuthRefreshTokens::where('token', $refreshToken->token)->first();
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
