<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\SocialNetWorks;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\SocialNetworksResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SocialNetworksController extends Controller
{
 
    public function index()
    {
        $socialNetworks = SocialNetWorks::all();
        if ($socialNetworks->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return SocialNetworksResource::collection($socialNetworks);
    }

    public function show($id)
    {
        $socialNetworks = SocialNetWorks::findOrFail($id);
        return new SocialNetworksResource($socialNetworks);
    }
   
    public function getSocialNetworksByUsersId($usersId)
    {
        $user = Users::findOrFail($usersId);
        $userSocialNetworks = $user->social_networks;

        if ($userSocialNetworks->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return SocialNetworksResource::collection($userSocialNetworks);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'profile_url' => 'nullable|active_url',
            'username' => 'nullable|string'
        ]);

        $socialNetwork = SocialNetWorks::findOrFail($id);
        
        if($request['profile_url']) {
            if($socialNetwork->profile_url) {
                return response()->json([
                    'error' => 'field already has a value',
                    'message' => "sign in with another social network to register this profile link"
                ], 400);
            }
        }

        if($request['username']) {
            if($socialNetwork->username) {
                return response()->json([
                    'error' => 'field already has a value',
                    'message' => "sign in with another social network to register this username"
                ], 400);
            }
        }        

        $update = SocialNetWorks::where('id', $id)->update($request->all());

        if ($update) {
            return new SocialNetWorksResource(SocialNetWorks::find($id));
        }

        return response()->json([
            'message' => 'could not update social network data',
        ], 409);
    }

    public function destroy($id)
    {
        $socialNetwork = SocialNetWorks::findOrFail($id);

        if($socialNetwork->users->password === null){
            return response()->json([
                'error' => 'user does not have password',
                'message' => 'please define a password to delete a social account',
            ], 400);
        }

        $delete = $socialNetwork->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete social network',
        ], 400);
    }
}
