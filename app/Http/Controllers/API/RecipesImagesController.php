<?php

namespace App\Http\Controllers\API;

use App\Recipes;
use App\RecipesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecipesImagesController extends Controller
{

    public function upload(Request $request, $id)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'thumbnail' => 'required|boolean'
        ]);

        $recipe = Recipes::find($id);

        if (!$recipe) {
            throw new ModelNotFoundException;
        }

        $basePath = 'uploads/recipes/images/';
        $urlBasePath = url($basePath);
        $file = $request->file('image');

        $image = new RecipesImages();
        $image->thumbnail = $request['thumbnail'];
        $image->original_filename = $file->getClientOriginalName();
        $image->original_extension = $file->getClientOriginalExtension();
        $image->mime = $file->getClientMimeType();

        $storeImage = $image->store($basePath, 'public');

        $image->filename = basename($storeImage);
        $image->picture_url = $urlBasePath.$image->filename;

        $database = $recipe->images()->save($image);
        // create collection resource
    }
    
}
