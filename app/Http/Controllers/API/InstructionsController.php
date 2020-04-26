<?php

namespace App\Http\Controllers\API;

use App\Recipes;
use App\Instructions;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\InstructionsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstructionsController extends Controller
{
 
    public function index()
    {
        $instructions = Instructions::all();
        if ($instructions->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return InstructionsResource::collection($instructions);
    }

    public function show($id)
    {
        $instruction = Instructions::findOrFail($id);
        return new InstructionsResource($instruction);
    }
   
    public function getInstructionsByRecipesId($recipesId)
    {
        Recipes::findOrFail($recipesId);
        $recipeInstructions = Instructions::where('recipes_id', $recipesId)->orderBy('order', 'asc')->get();

        if ($recipeInstructions->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return InstructionsResource::collection($recipeInstructions);
    }

    public function store(Request $request, $recipesId)
    {
        $this->validate($request, [
            'order' => 'required|integer|numeric',
            'description' => 'required|string'
        ]);

        Recipes::findOrFail($recipesId);
        
        $recipeHasInstructionOrder = Instructions::where('recipes_id', $recipesId)->where('order', $request['order'])->first();
        
        if($recipeHasInstructionOrder) {
            return response()->json([
                'error' => 'duplicate order value',
                'message' => "order {$request['order']} already exist"
            ], 400);
        }

        $request['recipes_id'] = $recipesId;
        $instructions = Instructions::create($request->all());

        if ($instructions) {
            return new InstructionsResource($instructions);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'order' => 'nullable|integer|numeric',
            'description' => 'nullable|string'
        ]);

        $instruction = Instructions::findOrFail($id);
        
        $recipeHasInstructionOrder = Instructions::where('recipes_id', $instruction->recipes_id)->where('order', $request['order'])->first();
        
        if($recipeHasInstructionOrder) {
            return response()->json([
                'error' => 'duplicate order value',
                'message' => "order {$request['order']} already exist"
            ], 400);
        }

        $update = Instructions::where('id', $id)->update($request->all());

        if ($update) {
            return new InstructionsResource(Instructions::find($id));
        }

        return response()->json([
            'message' => 'could not update instructions data',
        ], 409);
    }


    public function destroy($id)
    {
        Instructions::findOrFail($id);

        $delete = Instructions::where('id', $id)->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete instructions data',
        ], 400);
    }
}
