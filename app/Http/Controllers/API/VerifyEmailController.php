<?php

namespace App\Http\Controllers\API;

use App\Users;
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

        if ($user->email_verified_at !== null) {
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


    public function resendVerification($id)
    {
        $emailVerification = EmailVerifications::findOrFail($id);
        $user = $emailVerification->users;

        if ($emailVerification->done) {
            return response()->json([
                "message" => "this token has already been used to verify an email before"
            ], 409);
        }

        $this->verifyEmailService->setToken($emailVerification->token);

        if ($this->verifyEmailService->isTokenExpired()) {
            $emailVerification->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the verification token has been expired'
            ], 422);
        }

        $notification = $this->verifyEmailService->sendVerifyEmail($user, $emailVerification);

        if ($notification) {
            return new EmailVerificationResource($emailVerification);
        }

        return response()->json([
            'error' => 'error sending email',
            'message' => 'we could not send the verification email'
        ], 422);
    }


    public function validation(Request $request, $id)
    {
        $this->validate($request, [
            'token' => 'required|string|exists:App\EmailVerifications,token',
            'signature' => 'required|string|exists:App\EmailVerifications,signature',
        ]);

        $emailVerification = EmailVerifications::findOrFail($id);
        $user = $emailVerification->users;

        if (($request->token != $emailVerification->token) || ($emailVerification->signature != $request->signature)) {
            return response()->json([
                "message" => "token or signature are invalid"
            ], 400);
        }

        if ($emailVerification->done) {
            return response()->json([
                "message" => "this token has already been used to verify an email before"
            ], 409);
        }

        $this->verifyEmailService->setToken($request->token);

        if ($this->verifyEmailService->isTokenExpired()) {
            $emailVerification->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the verification token has been expired'
            ], 422);
        }

        $this->verifyEmailService->encryptUser($user);

        if(!$this->verifyEmailService->isUserValid($user)) {
            return response()->json([
                'message' => 'invalid user'
            ], 422);
        }

        $updateUser = $user->update(['email_verified_at' => now()]);

        if ($updateUser) {
            $emailVerification->update(['done' => true]);
            $emailVerification->delete();

            return new UsersResource($user);
        }

        return response()->json([
            'message' => "it was not possible to verify this email"
        ], 400);
    }
}
