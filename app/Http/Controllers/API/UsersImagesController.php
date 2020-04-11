<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\UsersImages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UsersImagesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecipesImagesController extends Controller
{
    public function index($usersId)
    {
        Users::findOrFail($usersId);
        $usersImages = UsersImages::where('users_id', $usersId)->get();

        if ($usersImages->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return UsersImagesResource::collection($usersImages);
    }

    public function show($id)
    {
        $image = UsersImages::findOrFail($id);
        return new UsersImagesResource($image);
    }

    public function upload(Request $request, $usersId)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'thumbnail' => 'required|boolean'
        ]);

        $thumbnail = $request['thumbnail'];
        $user = Users::findOrFail($usersId);

        if ($thumbnail) {
            $userHasThumbnail = UsersImages::where('thumbnail', $thumbnail)->where('users_id', $usersId)->first();

            if ($userHasThumbnail) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'error' => 'The selected user already has a thumbnail image.'
                ], 400);
            }
        }

        $userHasImage = UsersImages::where('users_id', $usersId)->first();

        if (!$userHasImage && !$thumbnail) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected users id does not have a thumbnail image, please define a thumbnail.'
            ], 400);
        }

        $basePath = 'uploads/users/images';
        $urlBasePath = url('storage/' . $basePath);
        $file = $request->file('image');

        $image = new UsersImages();
        $image->thumbnail = $request['thumbnail'];
        $image->original_filename = $file->getClientOriginalName();
        $image->original_extension = $file->getClientOriginalExtension();
        $image->mime = $file->getClientMimeType();

        $storeImage = $file->store($basePath, 'public');

        $image->filename = basename($storeImage);
        $image->path = $storeImage;
        $image->picture_url = $urlBasePath . '/' . $image->filename;
        $user->images()->save($image);

        return new UsersImagesResource($image);
    }

    public function update(Request $request, $usersId, $id)
    {
        $this->validate($request, [
            'thumbnail' => [
                'required',
                'boolean',
                Rule::in([true, 1])
            ]
        ]);

        $userImage = UsersImages::findOrFail($id);

        if ($userImage->thumbnail) {
            return new UsersImagesResource($userImage);
        }

        $currentThumbnailImage = UsersImages::where('users_id', $usersId)->where('thumbnail', true)->first();

        if ($currentThumbnailImage) {
            $currentThumbnailImage->update(['thumbnail' => false]);
        }

        $newThumbnailImage = UsersImages::where('id', $id)->update(['thumbnail' => true]);

        if ($newThumbnailImage) {
            return new UsersImagesResource(UsersImages::find($id));
        }

        return response()->json([
            'message' => 'could not update users data'
        ], 409);
    }

    public function destroy($id)
    {
        $userImage = UsersImages::findOrFail($id);

        if ($userImage->thumbnail) {
            return response()->json([
                'message' => 'it is not possible to delete a user thumbnail',
            ], 400);
        }

        $deleteFile = Storage::delete('public/' . $userImage->path);
        $delete = $userImage->delete();

        if ($delete && $deleteFile) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete users image data',
        ], 400);
    }
}
