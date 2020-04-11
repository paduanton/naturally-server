<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Followers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FollowersController extends Controller
{
    
    public function follow($firstUsersId, $secondUsersId)
    {
        $user = Users::find($firstUsersId);
        $secondUser = Users::find($secondUsersId);
        $user->following()->save($secondUser);

        // $roleID = 1;
        // $user->roles()->attach($roleID);
    
    }

    
    public function getFollowers(Followers $following)
    {
        //
    }

    public function getFollowing(Followers $following)
    {
        //
    }

    public function unfollow(Followers $following)
    {
        // unfollowed_at
    }
}
