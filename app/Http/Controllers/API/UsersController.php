<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\AuthenticationService;
use App\Http\Resources\UsersResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Users;

class UsersController extends Controller
{
    protected $authService;

    public function __construct(AuthenticationService $auth)
    {
        $this->authService = $auth;
    }
    public function index()
    {
        $users = Users::all();
        if ($users->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($users);
    }

    public function show($id)
    {
        $user = Users::findOrFail($id);
        return new UsersResource($user);
    }

    public function getByUsername($username)
    {
        $user = Users::where('username', $username)->firstOrFail();

        if (!$user) {
            throw new ModelNotFoundException;
        }

        return new UsersResource($user);
    }

    public function search($name)
    {

        $users = Users::where('name', 'LIKE', "%{$name}%")
            ->orWhere('username', 'LIKE', "%{$name}%")
            ->get();

        if ($users->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($users);
    }


    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'nullable|string',
            'username' => 'nullable|string|unique:users',
            'email' => 'email|nullable|unique:users',
            'password' => 'nullable|confirmed|string',
            'birthday' => 'nullable|date',
        ]);

        Users::findOrFail($id);

        if ($request['password']) {
            $request['password'] = $this->authService->hashPassword(($request['password']));

            $hasChangedPassword = true;
            unset($request['password_confirmation']);
        }

        $user = Users::where('id', $id)->update($request->all());

        if ($user) {
            $user = Users::find($id);
            
            if (isset($hasChangedPassword) && $hasChangedPassword === true) {
                $this->authService->sendPasswordChangingAlert($user);
            }

            return new UsersResource($user);
        }

        return response()->json([
            'message' => 'could not update users data',
        ], 409);
    }

    public function destroy(Request $request, $id)
    {
        $user = Users::findOrFail($id);

        $logout = $request->user()->token()->revoke();
        $delete = $user->delete();

        if ($delete && $logout) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'account successfully deleted',
        ], 400);
    }
}
