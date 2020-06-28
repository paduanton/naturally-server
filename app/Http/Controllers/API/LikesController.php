<?php

namespace App\Http\Controllers\API;

use App\Likes;
use App\Users;
use App\Recipes;
use Illuminate\Http\Request;
use App\Services\RecipeService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecipesResource;
use App\Http\Resources\UsersLikesResource;
use App\Http\Resources\RecipesLikesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use eloquentFilter\QueryFilter\ModelFilters\ModelFilters;

class LikesController extends Controller
{
    protected $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function index()
    {
        $likes = Likes::all();

        if ($likes->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return RecipesLikesResource::collection($likes);
    }

    public function show($id)
    {
        $likes = Likes::findOrFail($id);
        return new RecipesLikesResource($likes);
    }

    public function getMoreLikedRecipes(ModelFilters $filters)
    {
        $recipes = DB::table('recipes')
            ->leftJoin('likes', 'likes.recipes_id', '=', 'recipes.id')
            ->where('likes.is_liked', true)
            ->whereNull('likes.deleted_at')
            ->whereNull('recipes.deleted_at')
            ->groupBy('recipes.id')
            ->orderByDesc('recipes.id')
            ->select('recipes.*');

        if ($filters->filters()) {
            $recipes = $recipes->filter($filters)->paginate();
        } else {
            $recipes = $recipes->paginate();
        }

        if ($recipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        $recipes = $this->recipeService->convertGenericObjectToRecipeModel($recipes);

        return RecipesResource::collection($recipes);
    }

    public function getLikesByUserId($userId)
    {
        $user = Users::findOrFail($userId);
        $likes = $user->likes;

        if ($likes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersLikesResource::collection($likes);
    }

    public function getLikesByRecipesId($recipesId)
    {
        $recipe = Recipes::findOrFail($recipesId);
        $likes = $recipe->likes;

        if ($likes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesLikesResource::collection($likes);
    }

    public function store(Request $request, $usersId, $recipesId)
    {
        $this->validate($request, [
            'is_liked' => 'required|boolean',
        ]);

        Users::findOrFail($usersId);
        Recipes::findOrFail($recipesId);

        $userHasLikedRecipe = Likes::where("users_id", $usersId)->where("recipes_id", $recipesId)->first();

        if ($userHasLikedRecipe) {
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
            return new RecipesLikesResource($like);
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

        $like = Likes::findOrFail($id);
        $update = $like->update(['is_liked' => $request['is_liked']]);

        if ($update) {
            return new RecipesLikesResource(Likes::find($id));
        }

        return response()->json([
            'message' => 'could not update likes data',
        ], 409);
    }

    public function destroy($id)
    {
        $like = Likes::findOrFail($id);

        if (!$like) {
            throw new ModelNotFoundException;
        }

        $delete = $like->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete like',
        ], 400);
    }
}
