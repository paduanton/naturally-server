<?php

namespace App\Http\Controllers\API;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\RecipesResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Recipes;

class RecipesController extends Controller
{

    public function index()
    {
        if (Recipes::all()->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection(Recipes::all());
    }

    public function show($id)
    {
        if (!Recipes::find($id)) {
            throw new ModelNotFoundException;
        }

        return new RecipesResource(Recipes::find($id));
    }

    public function getRecipesByUsersId($usersId)
    {
        $usersRecipes = Recipes::where('users_id', $usersId)->get();

        if ($usersRecipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection($usersRecipes);
    }

    public function store(Request $request)
    {
    }


    public function update(Request $request, Recipes $recipes)
    {
    }


    public function destroy(Recipes $recipes)
    {
    }
}
