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

    public function index(Request $request)
    {
        $recipes = [];
        $category = $request->query('category');
        $mealType = $request->query('mealType');

        if ($category && $mealType) {
            $recipes = Recipes::where('category', $category)->where('meal_type', $mealType)->get();
        } elseif ($category) {
            $recipes = Recipes::where('category', $category)->get();
        } elseif ($mealType) {
            $recipes = Recipes::where('meal_type', $mealType)->get();
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

    public function getRecipesByUsersId(Request $request, $usersId)
    {
        $usersRecipes = Recipes::where('users_id', $usersId);
        $category = $request->query('category');
        $mealType = $request->query('mealType');
        
        if ($category && $mealType) {
            $usersRecipes = $usersRecipes->where('category', $category)->where('meal_type', $mealType)->get();
        } elseif ($category) {
            $usersRecipes = $usersRecipes->where('category', $category)->get();
        } elseif ($mealType) {
            $usersRecipes = $usersRecipes->where('meal_type', $mealType)->get();
        } else {
            $usersRecipes = $usersRecipes->get();
        }

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
