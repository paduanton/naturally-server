<?php

namespace App\Http\Controllers\API;

use App\Recipes;
use App\RecipesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecipesImagesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecipesImagesController extends Controller
{

    public function upload(Request $request, $id)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'thumbnail' => 'required|boolean'
        ]);

        $thumbnail = $request['thumbnail'];
        $recipe = Recipes::findOrFail($id);

        if ($thumbnail) {
            $recipeHasThumbnail = RecipesImages::where('thumbnail', $thumbnail)->first();

            if($recipeHasThumbnail) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'error' => 'The selected recipes id already has a thumbnail image.'
                ], 400);
            }
        }

        $recipeHasImage = RecipesImages::where('recipes_id', $id)->first();

        if(!$recipeHasImage && !$thumbnail) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected recipes id does not have a thumbnail image, please define a thumbnail.'
            ], 400);
        }

        $basePath = 'uploads/recipes/images/';
        $urlBasePath = url($basePath);
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
}
