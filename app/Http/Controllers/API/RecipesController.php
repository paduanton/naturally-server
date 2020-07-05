<?php

namespace App\Http\Controllers\API;

use eloquentFilter\QueryFilter\ModelFilters\ModelFilters;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\FullRecipeResource;
use App\Http\Resources\RecipesResource;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Services\RecipeService;
use Illuminate\Http\Request;
use App\Rules\YoutubeURL;
use App\Recipes;
use App\Users;

class RecipesController extends Controller
{

    protected $recipeService;

    public function __construct()
    {
        $this->recipeService = new RecipeService();
    }

    public function index(ModelFilters $filters)
    {
        $recipes = [];

        if ($filters->filters()) {
            $recipes = Recipes::filter($filters)->paginate();
        } else {
            $recipes = Recipes::paginate();
        }

        if ($recipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection($recipes);
    }

    public function show($id)
    {
        $recipe = Recipes::findOrFail($id);
        return new FullRecipeResource($recipe);
    }

    public function getRecipesByUsersId(ModelFilters $filters, $usersId)
    {
        $user = Users::findOrFail($usersId);
        $userRecipes = $user->recipes();

        if ($filters->filters()) {
            $userRecipes = $userRecipes->filter($filters)->paginate();
        } else {
            $userRecipes = $userRecipes->paginate();
        }

        if ($userRecipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection($userRecipes);
    }

    public function getMealTypes()
    {
        $categories = $this->recipeService->getRecipeMealTypes();
        return response()->json($categories);
    }

    public function getCategories()
    {
        $categories = $this->recipeService->getRecipeCategories();
        return response()->json($categories);
    }

    public function search(ModelFilters $filters, $title)
    {

        $recipes = Recipes::where('title', 'LIKE', "%{$title}%");

        if ($filters->filters()) {
            $recipes = $recipes->filter($filters)->paginate();
        } else {
            $recipes = $recipes->paginate();
        }

        if ($recipes->isEmpty()) {
            throw new ModelNotFoundException("No recipe found");
        }

        return RecipesResource::collection($recipes);
    }

    public function store(Request $request, $usersId)
    {
        $this->validate($request, [
            'title' => 'required|string',
            'description' => 'required|string',
            'cooking_time' => 'required',
            'category' => [
                'required',
                'string',
                Rule::in($this->recipeService->getRecipeCategories())
            ],
            'meal_type' => [
                'required',
                'string',
                Rule::in($this->recipeService->getRecipeMealTypes())
            ],
            'youtube_video_url' => ['nullable', 'active_url', new YoutubeURL],
            'yields' => 'required|numeric',
            'cost' => 'required|integer|between:1,5',
            'complexity' => 'required|integer|between:1,5',
            'notes' => 'nullable|string'
        ]);

        Users::findOrFail($usersId);

        $request['users_id'] = $usersId;
        $recipes = Recipes::create($request->all());

        if ($recipes) {
            return new RecipesResource($recipes);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'cooking_time' => 'nullable',
            'category' => [
                'nullable',
                'string',
                Rule::in($this->recipeService->getRecipeCategories())
            ],
            'meal_type' => [
                'nullable',
                'string',
                Rule::in($this->recipeService->getRecipeMealTypes())
            ],
            'youtube_video_url' => ['nullable', 'active_url', new YoutubeURL],
            'yields' => 'nullable|numeric',
            'cost' => 'nullable|integer|between:1,5',
            'complexity' => 'nullable|integer|between:1,5',
            'notes' => 'nullable|string'
        ]);

        Recipes::findOrFail($id);

        $update = Recipes::where('id', $id)->update($request->all());

        if ($update) {
            return new RecipesResource(Recipes::find($id));
        }

        return response()->json([
            'message' => 'could not update recipes data',
        ], 409);
    }

    public function destroy($id)
    {
        Recipes::findOrFail($id);

        $delete = Recipes::where('id', $id)->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete recipes data',
        ], 400);
    }
}
