<?php

namespace App\Http\Controllers\API;

use App\Recipes;
use App\Ingredients;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\IngredientsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IngredientsController extends Controller
{

    public function index()
    {
        $ingredients = Ingredients::all();
        if ($ingredients->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return IngredientsResource::collection($ingredients);
    }

    public function show($id)
    {
        $ingredient = Ingredients::findOrFail($id);
        return new IngredientsResource($ingredient);
    }

    public function getIngredientsByRecipesId($recipesId)
    {
        Recipes::findOrFail($recipesId);
        $recipeIngredients = Recipes::where('users_id', $recipesId);

        if ($recipeIngredients->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return IngredientsResource::collection($recipeIngredients);
    }

    public function store(Request $request, $recipesId)
    {
        $this->validate($request, [
            'measure' => 'required|string',
            'description' => 'required|string'
        ]);

        Recipes::findOrFail($recipesId);

        $request['recipes_id'] = $recipesId;
        $ingredients = Ingredients::create($request->all());

        if ($ingredients) {
            return new IngredientsResource($ingredients);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'measure' => 'required|string',
            'description' => 'required|string'
        ]);

        Ingredients::findOrFail($id);

        $update = Ingredients::where('id', $id)->update($request->all());

        if ($update) {
            return new IngredientsResource(Ingredients::find($id));
        }

        return response()->json([
            'message' => 'could not update ingredients data',
        ], 409);
    }


    public function destroy($id)
    {
        Ingredients::findOrFail($id);

        $delete = Ingredients::where('id', $id)->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete ingredient data',
        ], 400);
    }
}
