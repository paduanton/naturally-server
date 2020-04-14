<?php

namespace App\Http\Controllers\API;

use App\Users;
use Carbon\Carbon;
use App\Followers;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsersResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FollowersController extends Controller
{

    public function getFollowers($id)
    {
        $user = Users::findOrFail($id);
        $userFollowers = $user->followers;

        if ($userFollowers->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($userFollowers);
    }

    public function getFollowing($id)
    {
        $user = Users::findOrFail($id);
        $whomUserIsFollowing = $user->following;

        if ($whomUserIsFollowing->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($whomUserIsFollowing);
    }

    public function getMutualFollowing($firstUsersId, $secondUsersId)
    {
        //
    }

    public function follow($firstUsersId, $secondUsersId)
    {
        $user = Users::findOrFail($firstUsersId);
        $followedUser = Users::findOrFail($secondUsersId);

        $relationshipBetweenUsers = $this->isFollowingRelationshipEstablished($firstUsersId, $secondUsersId);

        if($relationshipBetweenUsers) {
            return response()->json($relationshipBetweenUsers, 200);
        }

        $usersHistory = $this->hasBeenFollowedBefore($firstUsersId, $secondUsersId);

        if($usersHistory){
            $usersHistory->restore();
            $usersHistory->update(['followed_at' => Carbon::now()]);
            return response()->json($usersHistory, 201);
        }

        $follower = new Followers();
        $follower->users_id = $user;
        $follower->following_users_id = $followedUser;
        $follower->followed_at = Carbon::now();
        $follower->save();

        return response()->json($follower, 201);
    }

    public function unfollow($firstUsersId, $secondUsersId)
    {
        // unfollowed_at
    }

    protected function hasBeenFollowedBefore($firstUsersId, $secondUsersId)
    {
        $follower = Followers::where('users_id', $firstUsersId)->where('following_users_id', $secondUsersId)->trashed();

        if(!$follower) {
            return false;
        }

        return $follower;
    }

    protected function isFollowingRelationshipEstablished($firstUsersId, $secondUsersId)
    {
        $relationship = Followers::where('users_id', $firstUsersId)->where('following_users_id', $secondUsersId)->first();

        if(!$relationship) {
            return false;
        }

        return $relationship;
    }
}
