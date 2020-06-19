<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\AuthenticationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\UsersResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ProfileImages;
Use App\RatingsImages;
use App\RecipesImages;
use App\Users;

class UsersController extends Controller
{
    protected $authService;

    public function __construct(AuthenticationService $auth)
    {
        $this->authService = $auth;
        $this->defaultUserPicture = config('app.default_user_picture');
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

    public function getPublicProfile(Request $request, $login)
    {
        $user = null;

        if ($this->authService->isEmail($login)) {
            $user = Users::where('email', $login)->firstOrFail();
        } else {
            $user = $this->getByUsername($login);
        }

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'profile_picture' => $this->getUserThumbnail($user->id)
        ], 200);
    }

    public function getByUsername($username)
    {
        $user = Users::where('username', $username)->firstOrFail();
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

        if ($request['email']) {
            $request['email_verified_at'] = null;
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
        $permanentlyDelete = (bool) $request->query('permanentlyDelete');

        $logout = $request->user()->token()->revoke();

        if ($permanentlyDelete) {
            $this->deleteImageFiles(new RecipesImages);
            $this->deleteImageFiles(new ProfileImages);
            $this->deleteImageFiles(new RatingsImages);
            
            $delete = $user->forceDelete();
        } else {
            $delete = $user->delete();
        }

        if ($delete && $logout) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'unable to complete operation',
        ], 400);
    }

    protected function getUserThumbnail($userId)
    {
        $userThumbnail = ProfileImages::where('users_id', $userId)->where('thumbnail', true)->first();

        if (!$userThumbnail) {
            return $this->defaultUserPicture;
        }

        return $userThumbnail->picture_url;
    }

    protected function deleteImageFiles(Model $model)
    {
        $images = $model::all();

        foreach ($images as $image) {
            Storage::delete('public/' . $image->path);
        }
    }
}
