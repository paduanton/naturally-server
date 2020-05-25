<?php

namespace App\Http\Controllers\API;

use App\Users;
use Carbon\Carbon;
use App\PasswordResets;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Http\Resources\PasswordResetResource;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;

class ForgotPasswordController extends Controller
{
    protected $authService;

    public function __construct(AuthenticationService $auth)
    {
        $this->authService = $auth;
    }

    public function forgot(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string|email|exists:App\Users,email',
        ]);

        $now = Carbon::now();
        $user = Users::where('email', $request->email)->first();

        $passwordReset = [
            "email" => $user->email,
            "token" => $this->authService->getUniqueHash(),
            "done" => false,
            "expires_at" => $now->addDay()
        ];

        $passwordReset = PasswordResets::create($passwordReset);
        $notification = $user->notify(new PasswordResetRequest($passwordReset->token));

        if ($user && $passwordReset && $notification) {
            return new PasswordResetResource($passwordReset);
        }

        return response()->json([
            'error' => "notification error",
            'message' => "we could not create a password reset notification"
        ], 400);
    }
    
    public function getPasswordResetByToken($token)
    {
        $passwordReset = PasswordResets::where('token', $token)
            ->first();
        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        }
        return response()->json($passwordReset);
    }
    
    public function reset(Request $request, $token)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string'
        ]);
        $passwordReset = PasswordResets::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        $user = Users::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'message' => "We can't find a user with that e-mail address."
            ], 404);
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        return response()->json($user);
    }
}
