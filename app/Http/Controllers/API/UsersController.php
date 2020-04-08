<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\UsersResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Users;

class UsersController extends Controller
{

    public function index()
    {
        if (Users::all()->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection(Users::all());
    }

    public function show($id)
    {
        $user = Users::findOrFail($id);
        return new UsersResource($user);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'first_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'nickname' => 'nullable|string|unique:users',
            'email' => 'email|nullable|unique:users',
            'password' => 'nullable|confirmed|string',
            'birthday' => 'nullable|date',
        ]);

        Users::findOrFail($id);

        if ($request['password']) {
            $request['password'] = Hash::make($request['password']);
            unset($request['password_confirmation']);
        }

        $user = Users::where('id', $id)->update($request->all());

        if ($user) {
            return new UsersResource(Users::find($id));
        }

        return response()->json([
            'message' => 'could not update users data',
        ], 409);
    }

    public function destroy(Request $request, $id)
    {
        Users::findOrFail($id);

        $logout = $request->user()->token()->revoke();
        $delete = Users::find($id)->delete();

        if ($delete && $logout) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete users data',
        ], 400);
    }
}
