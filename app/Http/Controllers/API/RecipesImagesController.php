<?php

namespace App\Http\Controllers\API;

use App\Recipes;
use App\RecipesImages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\RecipesImagesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecipesImagesController extends Controller
{
    public function index($recipesId)
    {
        Recipes::findOrFail($recipesId);
        $recipesImages = RecipesImages::where('recipes_id', $recipesId)->get();

        if ($recipesImages->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RecipesImagesResource::collection($recipesImages);
    }

    public function show($id)
    {
        $image = RecipesImages::findOrFail($id);
        return new RecipesImagesResource($image);
    }

    public function upload(Request $request, $recipesId)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'thumbnail' => 'required|boolean',
            'title' => 'nullable|string',
            'alt' => 'nullable|string'
        ]);

        $thumbnail = $request['thumbnail'];
        $recipe = Recipes::findOrFail($recipesId);

        if ($thumbnail) {
            $recipeHasThumbnail = RecipesImages::where('thumbnail', $thumbnail)->where('recipes_id', $recipesId)->first();

            if ($recipeHasThumbnail) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'error' => 'The selected recipe already has a thumbnail image.'
                ], 400);
            }
        }

        $recipeHasImage = RecipesImages::where('recipes_id', $recipesId)->first();

        if (!$recipeHasImage && !$thumbnail) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected recipes id does not have a thumbnail image, please define a thumbnail.'
            ], 400);
        }

        $basePath = 'uploads/recipes/images';
        $urlBasePath = url('storage/' . $basePath);
        $file = $request->file('image');

        $image = new RecipesImages();
        $image->title = $request['title'] ?? null;
        $image->alt = $request['alt'] ?? null;
        $image->thumbnail = $request['thumbnail'];
        $image->original_filename = $file->getClientOriginalName();
        $image->original_extension = $file->getClientOriginalExtension();
        $image->mime = $file->getClientMimeType();

        $storeImage = $file->store($basePath, 'public');

        $image->filename = basename($storeImage);
        $image->path = $storeImage;
        $image->picture_url = $urlBasePath . '/' . $image->filename;
        $recipe->images()->save($image);

        return new RecipesImagesResource($image);
    }

    public function update(Request $request, $recipesId, $id)
    {
        $this->validate($request, [
            'thumbnail' => [
                'nullable',
                'boolean',
                Rule::in([true, 1, "1"]),
                'required_without_all:title,alt'
            ],
            'title' => 'nullable|string',
            'alt' => 'nullable|string'
        ]);

        $recipeImage = RecipesImages::findOrFail($id);

        if (isset($request['thumbnail']) && $request['thumbnail'] == true) {
            if ($recipeImage->thumbnail) {
                $image = $recipeImage;
            } else {
                $currentThumbnailImage = RecipesImages::where('recipes_id', $recipesId)->where('thumbnail', true)->first();

                if ($currentThumbnailImage) {
                    $currentThumbnailImage->update(['thumbnail' => false]);
                }

                $image = RecipesImages::where('id', $id)->update(['thumbnail' => true]);
            }
        }

        if (isset($request['title']) || isset($request['alt'])) {
            $title = $request['title'] ?? null;
            $alt = $request['alt'] ?? null;

            $image = RecipesImages::where('id', $id)->update(['title' => $title, 'alt' => $alt]);
        }

        if ($image) {
            return new RecipesImagesResource(RecipesImages::find($id));
        }

        return response()->json([
            'message' => 'could not update recipe image data'
        ], 409);
    }

    public function destroy($recipeId, $id)
    {
        $recipe = Recipes::findOrFail($recipeId);
        $image = RecipesImages::findOrFail($id);

        if ($image->recipes->id !== $recipe->id) {
            return response()->json([
                'message' => "it's not possible to delete another recipe's picture",
            ], 400);
        }

        $userImages = $recipe->images;
        $imagesCount = sizeof($userImages);
        $isThumbnail = (bool) $image->thumbnail;

        if ($imagesCount > 1 && $isThumbnail) {
            return response()->json([
                'message' => 'it is not possible to delete a recipe thumbnail image',
            ], 400);
        }

        $deleteFile = Storage::delete('public/' . $image->path);
        $deleteEntity = $image->delete();

        if ($deleteEntity && $deleteFile) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete recipe image data',
        ], 400);
    }
}
