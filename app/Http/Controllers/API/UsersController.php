<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Users as UsersResource;
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
        if (!Users::find($id)) {
            throw new ModelNotFoundException;
        }

        return new UsersResource(Users::find($id));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'nickname' => 'nullable|string|unique:users',
            'email' => 'email|nullable|unique:users',
            'password' => 'nullable|confirmed|string',
            'birthday' => 'nullable|date',
            'picture_url' => 'nullable|url'
        ]);

        if ($request['password']) {
            $request['password'] = Hash::make($request['password']);
            unset($request['password_confirmation']);
        }

        $update = Users::where('id', $id)->update($request->all());

        if ($update) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not update users data',
        ], 400);
    }

    public function destroy(Users $users)
    {
        //
    }
}
