<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Recipes;
use App\UsersFavoritesRecipes;
use App\Http\Controllers\Controller;
use App\Http\Resources\FavoritesRecipesResource;
use App\Http\Resources\UsersFavoritesRecipesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersFavoritesRecipesController extends Controller
{

    public function index()
    {
        $favoriteRecipes = UsersFavoritesRecipes::all();
        if ($favoriteRecipes->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return FavoritesRecipesResource::collection($favoriteRecipes);
    }

    public function show($id)
    {
        $favoriteRecipe = UsersFavoritesRecipes::findOrFail($id);
        return new UsersFavoritesRecipesResource($favoriteRecipe);
    }

    public function getFavoritesRecipesByUserId($userId) {
        $user = Users::findOrFail($userId);
        $userFavoriteRecipes = $user->favorite_recipes;

        if ($userFavoriteRecipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersFavoritesRecipesResource::collection($userFavoriteRecipes);
    }

    public function getFavoritesByRecipesId($recipeId)
    {
        $recipe = Recipes::findOrFail($recipeId);
        $favorites = $recipe->favorites;

        if ($favorites->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return FavoritesRecipesResource::collection($favorites);
    }

    public function store($userId, $recipeId)
    {
        Users::findOrFail($userId);
        Recipes::findOrFail($recipeId);
        
        $userHasFavoritedRecipe = UsersFavoritesRecipes::where("users_id", $userId)->where("recipes_id", $recipeId)->first();
        
        if($userHasFavoritedRecipe) {
            return response()->json([
                'error' => 'duplicate entry',
                'message' => 'an user can not favorite a recipe twice'
            ], 400);
        }

        $favoriteRecipe = [
            'users_id' => $userId,
            'recipes_id' => $recipeId,
        ];

        $favoriteRecipe = UsersFavoritesRecipes::create($favoriteRecipe);

        if ($favoriteRecipe) {
            return new FavoritesRecipesResource($favoriteRecipe);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function destroy($id)
    {
        $favoriteRecipe = UsersFavoritesRecipes::where('id', $id)->first();
        
        if(!$favoriteRecipe) {
            throw new ModelNotFoundException;
        }

        $delete = $favoriteRecipe->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete this favourite',
        ], 400);
    }
}
