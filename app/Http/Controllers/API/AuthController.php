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
            'first_name' => 'required',
            'last_name' => 'nullable|date',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed',
            'birthday' => 'nullable|date',
            'picture_url' => 'nullable|url'
        ]);

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
            'password' => 'required'
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
