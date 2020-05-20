<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\SocialNetworkAccounts;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\SocialNetworkAccountsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SocialNetworkAccountsController extends Controller
{
 
    public function index()
    {
        $socialNetworkAccounts = SocialNetworkAccounts::all();
        if ($socialNetworkAccounts->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return SocialNetworkAccountsResource::collection($socialNetworkAccounts);
    }

    public function show($id)
    {
        $socialNetworkAccounts = SocialNetworkAccounts::findOrFail($id);
        return new SocialNetworkAccountsResource($socialNetworkAccounts);
    }
   
    public function getSocialNetworksByUsersId($usersId)
    {
        $user = Users::findOrFail($usersId);
        $userSocialNetworkAccounts = $user->social_network_accounts;

        if ($userSocialNetworkAccounts->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return SocialNetworkAccountsResource::collection($userSocialNetworkAccounts);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'profile_url' => 'nullable|active_url',
            'username' => 'nullable|string'
        ]);

        $socialNetworkAccount = SocialNetworkAccounts::findOrFail($id);
        
        if($request['profile_url']) {
            if($socialNetworkAccount->profile_url) {
                return response()->json([
                    'error' => 'field already has a value',
                    'message' => "sign in with another social network to register this profile link"
                ], 400);
            }
        }

        if($request['username']) {
            if($socialNetworkAccount->username) {
                return response()->json([
                    'error' => 'field already has a value',
                    'message' => "sign in with another social network to register this username"
                ], 400);
            }
        }        

        $update = SocialNetworkAccounts::where('id', $id)->update($request->all());

        if ($update) {
            return new SocialNetworkAccountsResource(SocialNetworkAccounts::find($id));
        }

        return response()->json([
            'message' => 'could not update social network data',
        ], 409);
    }

    public function destroy($id)
    {
        $socialNetworkAccount = SocialNetworkAccounts::findOrFail($id);

        if($socialNetworkAccount->user->password === null){
            return response()->json([
                'error' => 'user does not have password',
                'message' => 'please define a password to delete a social account',
            ], 400);
        }

        $delete = $socialNetworkAccount->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete social network',
        ], 400);
    }
}
