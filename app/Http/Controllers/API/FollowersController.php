<?php

namespace App\Http\Controllers\API;

use App\Followers;
use App\Users;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FollowersController extends Controller
{
    
    public function store($firstUsersId, $secondUsersId)
    {
        $user = Users::find($firstUsersId);
        $secondUser = Users::find($secondUsersId);
        $user->following()->save($secondUser);
    }

    
    public function following(Followers $following)
    {
        //
    }

    public function followers(Followers $following)
    {
        //
    }

    public function destroy(Followers $following)
    {
        //
    }
}
