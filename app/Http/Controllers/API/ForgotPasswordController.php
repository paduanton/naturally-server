<?php

namespace App\Http\Controllers\API;

use App\Users;
use Carbon\Carbon;
use App\PasswordResets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsersResource;
use App\Services\ResetPasswordService;
use App\Services\AuthenticationService;
use App\Http\Resources\PasswordResetResource;

class ForgotPasswordController extends Controller
{
    protected $resetPasswordService;
    protected $authService;

    public function __construct(AuthenticationService $auth, ResetPasswordService $reset)
    {
        $this->authService = $auth;
        $this->resetPasswordService = $reset;
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

        $this->resetPasswordService->setResetPasswordToken($passwordReset->token);
        $notification = $this->resetPasswordService->sendResetLinkEmail($user);

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
        $passwordReset = PasswordResets::where('token', $token)->firstOrFail();

        if ($passwordReset->done) {
            return response()->json([
                "message" => "this token has already been used to reset a password"
            ], 409);
        }

        $this->resetPasswordService->setResetPasswordToken($token);
        
        if ($this->resetPasswordService->isTokenExpired()) {
            $passwordReset->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the reset password token has been expired'
            ], 422);
        }

        return new PasswordResetResource($passwordReset);
    }

    public function resetPassword(Request $request, $token)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|confirmed|string|min:6',
        ]);

        $user = Users::where('email', $request->email)->firstOrFail();
        $passwordReset = PasswordResets::where('token', $token)->firstOrFail();

        if ($passwordReset->done) {
            return response()->json([
                "message" => "this token has already been used to reset a password"
            ], 409);
        }

        if ($user->email !== $passwordReset->email) {
            return response()->json([
                "message" => "user email does not belong to this token"
            ], 422);
        }

        $this->resetPasswordService->setResetPasswordToken($token);
        if ($this->resetPasswordService->isTokenExpired()) {
            $passwordReset->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the reset password token has been expired'
            ], 422);
        }

        $newPassword = Hash::make($request->password);
        $updateUserPassword = $user->update(['password' => $newPassword]);

        if ($updateUserPassword) {
            $passwordReset->update(['done' => true]);
            $passwordReset->delete();

            $this->resetPasswordService->sendSuccessfullyResetedEmail($user);

            return new UsersResource($user);
        }

        return response()->json([
            'message' => "it was not possible to update user's password"
        ], 400);
    }
}
