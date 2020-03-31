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
        $validatedData = $this->validate($request, [
            'first_name' => 'required',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed'
        ]);

        if ($validatedData->fails()) {
            return response()->json(['error' => $validatedData->errors()], 400);
        }

        $request['password'] = Hash::make($request['password']);
        $user = Users::create($request->all());

        if ($user) {
            return response()->json($this->generateToken($user), 201);
        }

        return response()->json([
            'message' => "Couldn't sign user up"
        ], 400);
    }


    public function login(Request $request)
    {
        $validatedData = $this->validate($request, [
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if ($validatedData->fails()) {
            return response()->json(['error' => $validatedData->errors()], 400);
        }

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
                'mensagem' => "Invalid credentials",
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