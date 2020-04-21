<?php

namespace App\Http\Controllers\API;

use App\Users;
use App\ProfileImages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProfileImagesResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfileImagesController extends Controller
{
    public function index($usersId)
    {
        Users::findOrFail($usersId);
        $usersImages = ProfileImages::where('users_id', $usersId)->get();

        if ($usersImages->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return ProfileImagesResource::collection($usersImages);
    }

    public function show($id)
    {
        $image = ProfileImages::findOrFail($id);
        return new ProfileImagesResource($image);
    }

    public function getThumbnail($usersId)
    {
        Users::findOrFail($usersId);
        $userThumbnail = ProfileImages::where('users_id', $usersId)->where('thumbnail', true)->first();

        if(!$userThumbnail) {
            return response()->json([
                'thumbnail' => false,
                'picture_url' => config('app.default_user_picture')
            ], 200);
        }

        return new ProfileImagesResource($userThumbnail);
    }

    public function upload(Request $request, $usersId)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'thumbnail' => 'required|boolean'
        ]);

        $user = Users::findOrFail($usersId);
        $thumbnail = $request['thumbnail'];

        if ($thumbnail) {
            $userHasThumbnail = ProfileImages::where('thumbnail', $thumbnail)->where('users_id', $usersId)->first();

            if ($userHasThumbnail) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'error' => 'The selected user already has a thumbnail image.'
                ], 400);
            }
        }

        $userHasImage = ProfileImages::where('users_id', $usersId)->first();

        if (!$userHasImage && !$thumbnail) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected users id does not have a thumbnail image, please define a thumbnail.'
            ], 400);
        }

        $basePath = 'uploads/users/images';
        $urlBasePath = url('storage/' . $basePath);
        $file = $request->file('image');

        $image = new ProfileImages();
        $image->thumbnail = $request['thumbnail'];
        $image->original_filename = $file->getClientOriginalName();
        $image->original_extension = $file->getClientOriginalExtension();
        $image->mime = $file->getClientMimeType();

        $storeImage = $file->store($basePath, 'public');

        $image->filename = basename($storeImage);
        $image->path = $storeImage;
        $image->picture_url = $urlBasePath . '/' . $image->filename;
        $user->images()->save($image);

        return new ProfileImagesResource($image);
    }

    public function update(Request $request, $usersId, $id)
    {
        $this->validate($request, [
            'thumbnail' => [
                'required',
                'boolean',
                Rule::in([true, 1, "1"])
            ]
        ]);

        $userImage = ProfileImages::findOrFail($id);

        if ($userImage->thumbnail) {
            return new ProfileImagesResource($userImage);
        }

        $currentThumbnailImage = ProfileImages::where('users_id', $usersId)->where('thumbnail', true)->first();

        if ($currentThumbnailImage) {
            $currentThumbnailImage->update(['thumbnail' => false]);
        }

        $newThumbnailImage = ProfileImages::where('id', $id)->update(['thumbnail' => true]);

        if ($newThumbnailImage) {
            return new ProfileImagesResource(ProfileImages::find($id));
        }

        return response()->json([
            'message' => 'could not update users image data'
        ], 409);
    }

    public function destroy($id)
    {
        $userImage = ProfileImages::findOrFail($id);

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
