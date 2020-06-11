<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\RestoredAccounts;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsersResource;
use App\Services\RestoreAccountService;
use App\Services\AuthenticationService;
use App\Http\Resources\RestoredAccountsResource;

class RestoredAccountsController extends Controller
{
    protected $restoreAccountService;
    protected $authService;

    public function __construct(AuthenticationService $auth, RestoreAccountService $restoreService)
    {
        $this->authService = $auth;
        $this->restoreAccountService = $restoreService;
    }

    public function solicitation(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string|email',
        ]);

        $user = Users::where('email', $request['email'])->first();

        if ($user) {
            return response()->json([
                'message' => "this user has an active account"
            ], 400);
        }

        $user = Users::onlyTrashed()->where('email', $request['email'])->firstOrFail();

        $this->restoreAccountService->encryptUser($user);

        $restoreAccount = [
            "email" => $user->email,
            "token" => $this->authService->getUniqueHash(),
            "signature" => $this->restoreAccountService->getEncryptedUser(),
            "done" => false,
            "expires_at" => now()->addMinutes(30)
        ];

        $restoreAccount = RestoredAccounts::create($restoreAccount);

        $this->restoreAccountService->setToken($restoreAccount->token);
        $notification = $this->restoreAccountService->sendRestoreEmail($user, $restoreAccount);

        if ($user && $restoreAccount && $notification) {
            return new RestoredAccountsResource($restoreAccount);
        }

        return response()->json([
            'error' => "notification error",
            'message' => "we could not send restore email link"
        ], 400);
    }


    public function resendSolicitation($id)
    {
        $restoredAccount = RestoredAccounts::findOrFail($id);
        $user = Users::onlyTrashed()->where('email', $restoredAccount->email)->firstOrFail();

        if ($restoredAccount->done) {
            return response()->json([
                "message" => "this token has already been used to restore an account before"
            ], 409);
        }

        $this->restoreAccountService->setToken($restoredAccount->token);

        if ($this->restoreAccountService->isTokenExpired()) {
            $restoredAccount->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the restore token has been expired'
            ], 422);
        }

        $notification = $this->restoreAccountService->sendRestoreEmail($user, $restoredAccount);

        if ($notification) {
            return new RestoredAccountsResource($restoredAccount);
        }

        return response()->json([
            'error' => 'error sending email',
            'message' => 'we could not send the restore account email'
        ], 422);
    }

    public function validation(Request $request, $id)
    {
        $this->validate($request, [
            'token' => 'required|string|exists:App\RestoredAccounts,token',
            'signature' => 'required|string|exists:App\RestoredAccounts,signature',
        ]);

        $restoredAccount = RestoredAccounts::findOrFail($id);
        $user = Users::where('email', $restoredAccount->email)->firstOrFail();

        if ($user) {
            return response()->json([
                "message" => "user already has an active account"
            ], 400);
        }

        $user = Users::onlyTrashed()->where('email', $restoredAccount->email)->firstOrFail();

        if (($request->token != $restoredAccount->token) || ($restoredAccount->signature != $request->signature)) {
            return response()->json([
                "message" => "token or signature are invalid"
            ], 400);
        }

        if ($restoredAccount->done) {
            return response()->json([
                "message" => "this token has already been used to restore an account before"
            ], 409);
        }

        $this->restoreAccountService->setToken($request->token);

        if ($this->restoreAccountService->isTokenExpired()) {
            $restoredAccount->delete();
            return response()->json([
                'error' => 'token expired',
                'message' => 'the restore token has been expired'
            ], 422);
        }

        $this->restoreAccountService->encryptUser($user);

        if (!$this->restoreAccountService->isUserValid($user)) {
            return response()->json([
                'message' => 'invalid user'
            ], 422);
        }

        $restoreUser = $user->restore();

        if ($restoreUser) {
            $restoredAccount->update(['done' => true]);
            $restoredAccount->delete();

            return new UsersResource($user);
        }

        return response()->json([
            'message' => "it was not possible to restore this account"
        ], 400);
    }
}
