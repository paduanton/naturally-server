<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Users;

class AuthController extends Controller
{

    public function signup(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed|string',
            'birthday' => 'nullable|date'
        ]);
        
        $request['username'] = $this->generateUsername($request['first_name'], $request['last_name']);
        $request['password'] = Hash::make($request['password']);
        $user = Users::create($request->all());

        if ($user) {
            return response()->json($this->generateToken($user), 201);
        }

        return response()->json([
            'message' => "couldn't sign user up"
        ], 400);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'email|required',
            'password' => 'required|string'
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => "Invalid credentials",
            ], 401);
        }

        $user = $request->user();
        return response()->json($this->generateToken($user));
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

    protected function generateUsername($firstName, $lastName)
    {
        $firstName = strtolower($firstName);
        $lastName = strtolower($lastName);
        $username = $firstName . "." . $lastName;

        $username = str_replace(" ", "", $username);
        $username = iconv('UTF-8','ASCII//TRANSLIT', $username);

        $user = Users::where('username', $username)->firstOrFail();

        while ($user) {
            $randomNumber = mt_rand();
            $username = $username . $randomNumber;

            $user = Users::where('username', $username)->firstOrFail();
        }

        return $username;
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
