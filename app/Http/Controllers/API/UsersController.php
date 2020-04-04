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
            return response()->json([
                'message' => 'No given data',
                'error' => 'There is no users to show.'
            ], 404);
        }

        return UsersResource::collection(Users::all());
    }

    public function show($id)
    {
        if (!Users::find($id)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected users id is invalid.'
            ], 404);
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
            'picture_url' => 'nullable|active_url'
        ]);

        $checkUser = $this->isUserExistent($id);

        if (!$checkUser) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected users id is invalid.'
            ], 404);
        }

        if ($request['password']) {
            $request['password'] = Hash::make($request['password']);
            unset($request['password_confirmation']);
        }

        $update = Users::where('id', $id)->update($request->all());

        if ($update) {
            return new UsersResource(Users::find($id));
        }

        return response()->json([
            'message' => 'could not update users data',
        ], 409);
    }

    public function destroy(Request $request, $id)
    {
        $checkUser = $this->isUserExistent($id);

        if (!$checkUser) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected users id is invalid.'
            ], 404);
        }

        $logout = $request->user()->token()->revoke();
        $delete = Users::find($id)->delete();

        if ($delete && $logout) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete users data',
        ], 400);
    }

    protected function isUserExistent($id)
    {
        $users = Users::find($id);

        if (!$users) {
            return false;
        }

        return true;
    }
}
