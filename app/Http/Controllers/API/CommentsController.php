<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Recipes;
use App\Comments;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CommentsController extends Controller
{
 
    public function index()
    {
        $comments = Comments::all();
        if ($comments->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return CommentsResource::collection($comments);
    }

    public function show($id)
    {
        $comment = Comments::findOrFail($id);
        return new CommentsResource($comment);
    }
   
    public function getCommentsByRecipesId($recipesId)
    {
        $recipe = Recipes::findOrFail($recipesId);
        $comments = Comments::where('recipes_id', $recipesId)->get();

        if ($comments->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return CommentsResource::collection($comments);
    }

    public function store(Request $request, $usersId, $recipesId)
    {
        $this->validate($request, [
            'description' => 'required|string',
            'parent_comments_id' => 'nullable|integer|exists:App\Comments,id'
        ]);
        
        Users::findOrFail($usersId);
        Recipes::findOrFail($recipesId);
        
        if(isset($request['parent_comments_id'])) {
            $comment = Comments::findOrFail($request['parent_comments_id']);

            if($comment->parent_comments_id) {
                return response()->json([
                    'error' => 'invalid reply',
                    'message' => 'a reply cannot have another reply'
                ], 400);
            }
        }

        $comment = [
            'description' => $request['description'],
            'users_id' => $usersId,
            'recipes_id' => $recipesId,
            'parent_comments_id' => isset($request['parent_comments_id']) ? $request['parent_comments_id'] : null
        ];

        $comment = Comments::create($comment);        

        if ($comment) {
            return new CommentsResource($comment);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'description' => 'nullable|string'
        ]);

        $instruction = Comments::findOrFail($id);
        
        $recipeHasInstructionOrder = Comments::where('recipes_id', $instruction->recipes_id)->where('order', $request['order'])->first();
        
        if($recipeHasInstructionOrder) {
            return response()->json([
                'error' => 'duplicate order value',
                'message' => "order {$request['order']} already exist"
            ], 400);
        }

        $update = Comments::where('id', $id)->update($request->all());

        if ($update) {
            return new CommentsResource(Comments::find($id));
        }

        return response()->json([
            'message' => 'could not update instructions data',
        ], 409);
    }


    public function destroy($id)
    {
        Comments::findOrFail($id);

        $delete = Comments::where('id', $id)->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete comment',
        ], 400);
    }
}
