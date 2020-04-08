<?php

namespace App\Http\Controllers\API;

use eloquentFilter\QueryFilter\ModelFilters\ModelFilters;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\RecipesResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Recipes;
use App\Users;

class RecipesController extends Controller
{

    public function index(ModelFilters $filters, Request $request)
    {
        $recipes = [];

        if ($filters->filters()) {
            $recipes = Recipes::filter($filters)->get();
        } else {
            $recipes = Recipes::all();
        }

        if ($recipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection($recipes);
    }

    public function show($id)
    {
        $recipe = Recipes::findOrFail($id);
        return new RecipesResource($recipe);
    }

    public function getRecipesByUsersId(ModelFilters $filters, $usersId)
    {
        $usersId = Users::findOrFail($usersId);
        $usersRecipes = Recipes::where('users_id', $usersId);

        if ($filters->filters()) {
            $usersRecipes = $usersRecipes->filter($filters)->get();
        } else {
            $usersRecipes = $usersRecipes->get();
        }

        if ($usersRecipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection($usersRecipes);
    }

    public function store(Request $request, $usersId)
    {
        $this->validate($request, [
            'title' => 'required|string',
            'description' => 'required|string',
            'cooking_time' => 'required',
            'category' => 'required|string',
            'meal_type' => 'required|string',
            'video_url' => 'nullable|active_url',
            'yields' => 'required|numeric',
            'cost' => 'required|integer|between:1,5',
            'complexity' => 'required|integer|between:1,5',
            'notes' => 'nullable|string'
        ]);

        $usersId = Users::findOrFail($usersId);

        $request['users_id'] = $usersId;
        $recipes = Recipes::create($request->all());

        if ($recipes) {
            return new RecipesResource($recipes);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }


    public function update(Request $request, $usersId, $id)
    {
        $this->validate($request, [
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'cooking_time' => 'nullable',
            'category' => 'nullable|string',
            'meal_type' => 'nullable|string',
            'video_url' => 'nullable|active_url',
            'yields' => 'nullable|numeric',
            'cost' => 'nullable|integer|between:1,5',
            'complexity' => 'nullable|integer|between:1,5',
            'notes' => 'nullable|string'
        ]);
        
        $id = Recipes::findOrFail($id);
        $usersId = Users::findOrFail($id);

        if ($request['users_id']) {
            return response()->json([
                'message' => 'can not change users id of recipes',
            ], 409);
        }

        $update = Recipes::where('id', $id)->where('users_id', $usersId)->update($request->all());

        if ($update) {
            return new RecipesResource(Recipes::find($id));
        }

        return response()->json([
            'message' => 'could not update users data',
        ], 409);
    }


    public function destroy($usersId, $id)
    {
        $id = Recipes::findOrFail($id);
        $usersId = Users::findOrFail($id);


        $delete = Recipes::where('id', $id)->where('users_id', $usersId)->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete recipes data',
        ], 400);
    }
}
