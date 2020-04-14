<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Followers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FollowersController extends Controller
{

    public function getFollowers($id)
    {
        // select users_id from followers where following_users_id = :id
        $user = Users::find($id);

        return response()->json($user->followers, 200);
    }

    public function getFollowing($id)
    {
        // select following_users_id from followers where users_id = :id

        $user = Users::find($id);

        return response()->json($user->following, 200);
    }

    public function follow($firstUsersId, $secondUsersId)
    {
        $user = Users::find($firstUsersId);
        $secondUser = Users::find($secondUsersId);
        $user->following()->save($secondUser);

        // $roleID = 1;
        // $user->roles()->attach($roleID);

    }

    public function getMutualFollowers($firstUsersId, $secondUsersId)
    {
        //
    }

    public function unfollow($firstUsersId, $secondUsersId)
    {
        // unfollowed_at
    }
}
