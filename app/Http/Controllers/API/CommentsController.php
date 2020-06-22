<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\Recipes;
use App\Comments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use eloquentFilter\QueryFilter\ModelFilters\ModelFilters;

class CommentsController extends Controller
{

    public function index()
    {
        $comments = Comments::paginate();

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

    public function getCommentsByRecipesId(ModelFilters $filters, $recipesId)
    {
        $recipe = Recipes::findOrFail($recipesId);
        $recipeComments = $recipe->comments();

        if ($filters->filters()) {
            $recipeComments = $recipeComments->filter($filters)->paginate();
        } else {
            $recipeComments = $recipeComments->paginate();
        }

        if ($recipeComments->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return CommentsResource::collection($recipeComments);
    }

    public function store(Request $request, $usersId, $recipesId)
    {
        $this->validate($request, [
            'description' => 'required|string',
            'parent_comments_id' => 'nullable|integer|exists:App\Comments,id'
        ]);

        Users::findOrFail($usersId);
        Recipes::findOrFail($recipesId);

        if (isset($request['parent_comments_id'])) {
            $comment = Comments::find($request['parent_comments_id']);

            if (isset($comment->parent_comments_id)) {
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
            'description' => 'required|string'
        ]);

        $comment = Comments::findOrFail($id);

        $now = Carbon::now();
        $createdAt = $comment->created_at;

        $diferenceBetweenDates = $createdAt->diffInSeconds($now);

        if ($diferenceBetweenDates > 300) { // 300s = 5 minutes
            return response()->json([
                'message' => 'it is not possible to update a comment created more than 5 minutes ago',
            ], 409);
        }

        $update = Comments::where('id', $id)->update(['description' => $request['description']]);

        if ($update) {
            return new CommentsResource(Comments::find($id));
        }

        return response()->json([
            'message' => 'could not update comments data',
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
