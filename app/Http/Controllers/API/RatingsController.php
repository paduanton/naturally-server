<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Recipes;
use App\Ratings;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\RatingsResource;
use App\Http\Resources\UsersRatingsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RatingsController extends Controller
{

    public function index()
    {
        $ratings = Ratings::all();
        if ($ratings->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return RatingsResource::collection($ratings);
    }

    public function show($id)
    {
        $rating = Ratings::findOrFail($id);
        return new UsersRatingsResource($rating);
    }

    public function getRatingsByUserId($userId) {
        $user = Users::findOrFail($userId);
        $ratings = $user->ratings;

        if ($ratings->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersRatingsResource::collection($ratings);
    }

    public function getRatingsByRecipeId($recipeId)
    {
        $recipe = Recipes::findOrFail($recipeId);
        $ratings = $recipe->ratings;

        if ($ratings->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RatingsResource::collection($ratings);
    }

    public function store(Request $request, $userId, $recipeId)
    {
        $this->validate($request, [
            'made_it' => 'nullable|boolean',
            'value' => 'required|integer|between:1,5',
            'description' => 'nullable|string',
        ]);

        $user = Users::findOrFail($userId);
        $recipe = Recipes::findOrFail($recipeId);
        
        if($user->id === $recipe->users_id) {
            return response()->json([
                'message' => 'an user can not rate its own recipe'
            ], 400);
        }

        $userHasRatedRecipe = Ratings::where("users_id", $userId)->where("recipes_id", $recipeId)->first();
        
        if($userHasRatedRecipe) {
            return response()->json([
                'error' => 'duplicate entry',
                'message' => 'an user can not rate a recipe twice'
            ], 400);
        }

        $request['users_id'] = $userId;
        $request['recipes_id'] = $recipeId;

        $rating = Ratings::create($request->all());

        if ($rating) {
            return new RatingsResource($rating);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'made_it' => 'nullable|boolean',
            'value' => 'nullable|integer|between:1,5',
            'description' => 'nullable|string',
        ]);
        
        Ratings::findOrFail($id);

        $update = Ratings::where('id', $id)->update($request->all());

        if ($update) {
            return new RatingsResource(Ratings::find($id));
        }

        return response()->json([
            'message' => 'could not update ratings data',
        ], 409);
    }

    public function destroy($id)
    {
        $rating = Ratings::where('id', $id)->first();
        
        if(!$rating) {
            throw new ModelNotFoundException;
        }

        $delete = $rating->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete this rating',
        ], 400);
    }
}
