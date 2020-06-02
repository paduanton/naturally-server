<?php

namespace App\Http\Controllers\API;

use App\Users;
use Carbon\Carbon;
use App\EmailVerifications;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsersResource;
use App\Services\VerifyEmailService;
use App\Services\AuthenticationService;
use App\Http\Resources\EmailVerificationResource;

class VerifyEmailController extends Controller
{
    protected $verifyEmailService;
    protected $authService;

    public function __construct(AuthenticationService $auth, VerifyEmailService $verifyService)
    {
        $this->authService = $auth;
        $this->verifyEmailService = $verifyService;
    }

    public function verify(Request $request, $userId)
    {
        $this->validate($request, [
            'email' => 'required|string|email|exists:App\Users,email',
        ]);

        $user = Users::findOrFail($userId);
        
        if($user->email_verified_at !== null) {
            return response()->json([
                'message' => "user has already verified its account"
            ], 422);
        }

        if ($user->email != $request->email) {
            return response()->json([
                'error' => "notification error",
                'message' => "this email doesn't belong to this specified user"
            ], 422);
        }

        $this->verifyEmailService->encryptUser($user);

        $emailVerification = [
            "email" => $user->email,
            "token" => $this->authService->getUniqueHash(),
            "signature" => $this->verifyEmailService->getEncryptedUser(),
            "done" => false,
            "expires_at" => now()->addDay()
        ];

        $emailVerification = EmailVerifications::create($emailVerification);

        $this->verifyEmailService->setToken($emailVerification->token);
        $notification = $this->verifyEmailService->sendVerifyEmail($user, $emailVerification);

        if ($user && $emailVerification && $notification) {
            return new EmailVerificationResource($emailVerification);
        }

        return response()->json([
            'error' => "notification error",
            'message' => "we could not send verification email link"
        ], 400);
    }
    /*
    public function validate($token)
    {
        $emailVerification = EmailVerifications::where('token', $token)->firstOrFail();

        if ($emailVerification->done) {
            return response()->json([
                "message" => "this token has already been used to reset a password"
            ], 409);
        }

        $this->verifyEmailService->setToken($token);

        if ($this->verifyEmailService->isTokenExpired()) {
            $emailVerification->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the verify token has been expired'
            ], 422);
        }

        return new PasswordResetResource($passwordReset);
    }

    public function resendVerification(Request $request)
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

        $this->resetPasswordService->setToken($token);
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
*/
}
