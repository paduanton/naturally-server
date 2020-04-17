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
            'thumbnail' => 'required|boolean'
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
                'required',
                'boolean',
                Rule::in([true, 1, "1"])
            ]
        ]);

        $recipeImage = RecipesImages::findOrFail($id);

        if ($recipeImage->thumbnail) {
            return new RecipesImagesResource($recipeImage);
        }

        $currentThumbnailImage = RecipesImages::where('recipes_id', $recipesId)->where('thumbnail', true)->first();

        if ($currentThumbnailImage) {
            $currentThumbnailImage->update(['thumbnail' => false]);
        }

        $newThumbnailImage = RecipesImages::where('id', $id)->update(['thumbnail' => true]);

        if ($newThumbnailImage) {
            return new RecipesImagesResource(RecipesImages::find($id));
        }

        return response()->json([
            'message' => 'could not update recipe image data'
        ], 409);
    }

    public function destroy($id)
    {
        $recipeImage = RecipesImages::findOrFail($id);

        if ($recipeImage->thumbnail) {
            return response()->json([
                'message' => 'it is not possible to delete a recipe thumbnail',
            ], 400);
        }

        $deleteFile = Storage::delete('public/' . $recipeImage->path);
        $delete = $recipeImage->delete();

        if ($delete && $deleteFile) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete recipes image data',
        ], 400);
    }
}
