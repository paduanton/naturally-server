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

    protected function refreshToken(Request $request)
    {
    }

    protected function generateRefreshToken($tokenId)
    {   
        $uniqueHash = md5(mt_rand() . microtime() . uniqid()); 

        $refreshToken = new OAuthRefreshTokens();
        $refreshToken->id = $uniqueHash; 
        $refreshToken->access_token_id = $tokenId;
        $refreshToken->token = $uniqueHash . '?' . Str::uuid() . Str::random(50);
        $refreshToken->revoked = false;
        $refreshToken->expires_at = Carbon::now()->addYear();

        $findByToken = OAuthRefreshTokens::where('token', $refreshToken->token)->first();
        $findById = OAuthRefreshTokens::find($refreshToken->id);

        while ($findById || $findByToken) {
            $uniqueHash = md5(mt_rand() . microtime() . uniqid()); 

            $refreshToken->id = $uniqueHash;
            $refreshToken->token = $uniqueHash . '?' . Str::uuid() . Str::random(50);

            $findById = OAuthRefreshTokens::find($refreshToken->id);
            $findByToken = OAuthRefreshTokens::where('token', $refreshToken->token)->first();
        }
        
        $refreshToken->save();

        return $refreshToken->token;
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

    protected function generateAccessToken($user)
    {
        $token = $user->createToken('Personal Access Token');
        $accessToken = $token->accessToken;

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $token->token->expires_at
            )->toDateTimeString(),
            'refresh_token' => $this->generateRefreshToken($token->token->id)
        ];
    }
}
