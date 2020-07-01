<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Recipes;
use App\UsersFavoriteRecipes;
use App\Http\Controllers\Controller;
use App\Http\Resources\FavoritesRecipesResource;
use App\Http\Resources\UsersFavoriteRecipesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersFavoriteRecipesController extends Controller
{

    public function index()
    {
        $favoriteRecipes = UsersFavoriteRecipes::all();
        if ($favoriteRecipes->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return FavoritesRecipesResource::collection($favoriteRecipes);
    }

    public function show($id)
    {
        $favoriteRecipe = UsersFavoriteRecipes::findOrFail($id);
        return new UsersFavoriteRecipesResource($favoriteRecipe);
    }

    public function getFavoritesRecipesByUserId($userId) {
        $user = Users::findOrFail($userId);
        $userFavoriteRecipes = $user->favorite_recipes;

        if ($userFavoriteRecipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersFavoriteRecipesResource::collection($userFavoriteRecipes);
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
        
        $userHasFavoritedRecipe = UsersFavoriteRecipes::where("users_id", $userId)->where("recipes_id", $recipeId)->first();
        
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

        $favoriteRecipe = UsersFavoriteRecipes::create($favoriteRecipe);

        if ($favoriteRecipe) {
            return new FavoritesRecipesResource($favoriteRecipe);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function destroy($id)
    {
        $favoriteRecipe = UsersFavoriteRecipes::where('id', $id)->first();
        
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
