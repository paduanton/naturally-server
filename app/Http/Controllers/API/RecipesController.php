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
        if (!Recipes::find($id)) {
            throw new ModelNotFoundException;
        }

        return new RecipesResource(Recipes::find($id));
    }

    public function getRecipesByUsersId(ModelFilters $filters, $usersId)
    {
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

        $user = Users::find($usersId);

        if (!$user) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected users id is invalid.'
            ], 404);
        }

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

        $checkIds = $this->isUsersAndRecipesExistents($usersId, $id);

        if (!$checkIds) {
            throw new ModelNotFoundException;
        }

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
        $checkIds = $this->isUsersAndRecipesExistents($usersId, $id);

        if (!$checkIds) {
            throw new ModelNotFoundException;
        }

        $delete = Recipes::where('id', $id)->where('users_id', $usersId)->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete recipes data',
        ], 400);
    }

    protected function isUsersAndRecipesExistents($usersId, $recipesId = null)
    {
        $recipes = Recipes::find($recipesId);
        $users = Users::find($usersId);

        if (!$users) {
            return false;
        }

        if (!$recipes) {
            return false;
        }

        return true;
    }
}
