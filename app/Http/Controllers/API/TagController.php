<?php

namespace App\Http\Controllers\API;

use App\Tags;
use App\Recipes;
use App\RecipesTags;
use App\Services\TagService;
use Illuminate\Http\Request;
use App\Http\Resources\TagResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecipesResource;
use App\Http\Resources\RecipesTagsResource;
use App\Http\Resources\RecipeTagRelationshipResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TagController extends Controller
{
    protected $tagService;

    public function __construct(TagService $service)
    {
        $this->tagService = $service;
    }

    public function index()
    {
        $tags = Tags::paginate();

        if ($tags->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return TagResource::collection($tags);
    }

    public function getRecipesByTag($hashtag) {
        $tag = Tags::where('hashtag', $hashtag)->firstOrFail();

        $tagRecipes = $tag->recipes();
        $tagRecipes = $tagRecipes->paginate();
        
        if ($tagRecipes->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesResource::collection($tagRecipes);
    }

    public function getTagsByRecipeId($recipesId)
    {
        $recipe = Recipes::findOrFail($recipesId);
        $recipeTags = $recipe->tags();

        $recipeTags = $recipeTags->paginate();
        
        if ($recipeTags->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipeTagRelationshipResource::collection($recipeTags);
    }

    public function store(Request $request, $recipeId)
    {
        $hashtagPattern = $this->tagService->getHashtagPattern();

        $this->validate($request, [
            'hashtag' => "required|regex:{$hashtagPattern}"
        ]);

        $recipe = Recipes::findOrFail($recipeId);

        $tag = Tags::firstOrCreate(['hashtag' => $request['hashtag']], ['hashtag' => $request['hashtag']]);

        $recipeTag = [
            'recipes_id' => $recipe->id,
            'tags_id' => $tag->id
        ];

        $tag = RecipesTags::firstOrCreate($recipeTag, $recipeTag);

        if ($tag) {
            return new RecipesTagsResource($tag);
        }

        return response()->json([
            'message' => 'could not store data'
        ], 400);
    }

    public function destroy($recipeId, $tagId)
    {
        Recipes::findOrFail($recipeId);
        Tags::findOrFail($tagId);

        $delete = RecipesTags::where('tags_id', $tagId)->where('recipes_id', $recipeId)->firstOrFail()->delete();

        if ($delete) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not remove tag from recipe',
        ], 400);
    }
}
