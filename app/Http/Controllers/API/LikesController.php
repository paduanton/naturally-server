<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Recipes;
use App\Likes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LikesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LikesController extends Controller
{

    public function index()
    {
        $likes = Likes::all();
        if ($likes->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return LikesResource::collection($likes);
    }

    public function show($id)
    {
        $likes = Likes::findOrFail($id);
        return new LikesResource($likes);
    }

    public function getLikesByRecipesId($recipesId)
    {
        Recipes::findOrFail($recipesId);
        $likes = Likes::where('recipes_id', $recipesId)->get();

        if ($likes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return LikesResource::collection($likes);
    }

    public function store(Request $request, $usersId, $recipesId)
    {
        $this->validate($request, [
            'is_liked' => 'required|boolean',
        ]);

        Users::findOrFail($usersId);
        Recipes::findOrFail($recipesId);
        
        $userHasLikedRecipe = Likes::where("users_id", $usersId)->where("recipes_id", $recipesId)->first();
        
        if($userHasLikedRecipe) {
            return response()->json([
                'error' => 'duplicate entry',
                'message' => 'an user can not like or dislike a recipe twice'
            ], 400);
        }

        $like = [
            'is_liked' => $request['is_liked'],
            'users_id' => $usersId,
            'recipes_id' => $recipesId,
        ];

        $like = Likes::create($like);

        if ($like) {
            return new LikesResource($like);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'is_liked' => 'required|boolean',
        ]);


        $like = Likes::where('id', $id)->first();
                
        $update = $like->update(['is_liked' => $request['is_liked']]);

        if ($update) {
            return new LikesResource(Likes::find($id));
        }

        return response()->json([
            'message' => 'could not update likes data',
        ], 409);
    }

    public function destroy($id)
    {
        $like = Likes::where('id', $id)->first();

        $delete = $like->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete like',
        ], 400);
    }
}
