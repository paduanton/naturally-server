<?php

namespace App\Http\Controllers\API;

use App\Users;
use Carbon\Carbon;
use App\Followers;
use Illuminate\Support\Facades\DB;
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
        $firstUser = Users::findOrFail($firstUsersId);
        $secondUser = Users::findOrFail($secondUsersId);

        $mutualFollowing = DB::table('users')
            ->join('followers as firstUserFollowing', 'users.id', '=', 'firstUserFollowing.following_users_id')
            ->where('firstUserFollowing.users_id', $firstUser->id)
            ->join('followers as secondUsersFollowing', 'users.id', '=', 'secondUsersFollowing.following_users_id')
            ->where('secondUsersFollowing.users_id', $secondUser->id)
            ->select('users.*')
            ->get();

        if ($mutualFollowing->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($mutualFollowing);
    }

    public function getMutualFollowers($firstUsersId, $secondUsersId)
    {
        $firstUser = Users::findOrFail($firstUsersId);
        $secondUser = Users::findOrFail($secondUsersId);

        $mutualFollowers = DB::table('users')
            ->join('followers as firstUserFollowers', 'users.id', '=', 'firstUserFollowers.users_id')
            ->where('firstUserFollowers.following_users_id', $firstUser->id)
            ->join('followers as secondUsersFollowers', 'users.id', '=', 'secondUsersFollowers.users_id')
            ->where('secondUsersFollowers.following_users_id', $secondUser->id)
            ->select('users.*')
            ->get();

        if ($mutualFollowers->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($mutualFollowers);
    }

    public function getFriends($id)
    {
        $user = Users::findOrFail($id);

        $friends = DB::table('users')
            ->join('followers as usersFollowing', 'users.id', '=', 'usersFollowing.following_users_id')
            ->where('usersFollowing.users_id', $user->id)
            ->join('followers as usersFollowers', 'users.id', '=', 'usersFollowers.users_id')
            ->where('usersFollowers.following_users_id', $user->id)
            ->select('users.*')
            ->get();

        if ($friends->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersResource::collection($friends);
    }

    public function follow($firstUsersId, $secondUsersId)
    {
        $user = Users::findOrFail($firstUsersId);
        $followedUser = Users::findOrFail($secondUsersId);

        if ($firstUsersId === $secondUsersId) {
            return response()->json(['message' => 'a user can not follow itself'], 400);
        }

        $usersRelationship = $this->isFollowingRelationshipEstablished($firstUsersId, $secondUsersId);

        if ($usersRelationship) {
            return response()->json($usersRelationship, 200);
        }

        $usersHistory = $this->hasBeenFollowedBefore($firstUsersId, $secondUsersId);

        if ($usersHistory) {
            $usersHistory->restore();

            $follower = Followers::where('id', $usersHistory->id)->update(['followed_at' => Carbon::now()]);
            $follower = Followers::find($usersHistory->id);

            return response()->json($follower, 201);
        }

        $follower = new Followers();
        $follower->users_id = $user->id;
        $follower->following_users_id = $followedUser->id;
        $follower->followed_at = Carbon::now()->toDateString();
        $follower->save();

        return response()->json($follower, 201);
    }

    public function unfollow($firstUsersId, $secondUsersId)
    {
        $user = Users::findOrFail($firstUsersId);
        $followedUser = Users::findOrFail($secondUsersId);

        $followRelationship = Followers::where('users_id', $user->id)->where('following_users_id', $followedUser->id)->firstOrFail();
        $delete = $followRelationship->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete follower data',
        ], 400);
    }

    protected function hasBeenFollowedBefore($firstUsersId, $secondUsersId)
    {
        $follower = Followers::onlyTrashed()->where('users_id', $firstUsersId)->where('following_users_id', $secondUsersId)->first();

        if ($follower) {
            return $follower;
        }

        return false;
    }

    protected function isFollowingRelationshipEstablished($firstUsersId, $secondUsersId)
    {
        $relationship = Followers::where('users_id', $firstUsersId)->where('following_users_id', $secondUsersId)->first();

        if (!$relationship) {
            return false;
        }

        return $relationship;
    }
}
