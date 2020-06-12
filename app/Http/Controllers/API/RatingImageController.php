<?php

namespace App\Http\Controllers\API;

use App\Ratings;
use App\RatingsImages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\RatingImageResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RatingImageController extends Controller
{
    public function index($ratingId)
    {
        $rating = Ratings::findOrFail($ratingId);
        $ratingImages = $rating->images;

        if ($ratingImages->isEmpty()) {
            throw new ModelNotFoundException;
        }

        return RatingImageResource::collection($ratingImages);
    }

    public function show($id)
    {
        $image = RatingsImages::findOrFail($id);
        return new RatingImageResource($image);
    }

    public function upload(Request $request, $ratingId)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'thumbnail' => 'required|boolean',
            'title' => 'nullable|string',
            'alt' => 'nullable|string'
        ]);

        $thumbnail = $request['thumbnail'];
        $rating = Ratings::findOrFail($ratingId);

        if ($thumbnail) {
            $ratingHasThumbnail = RatingsImages::where('thumbnail', $thumbnail)->where('ratings_id', $ratingId)->first();

            if ($ratingHasThumbnail) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'error' => 'The selected rating already has a thumbnail image.'
                ], 400);
            }
        }

        $ratingHasImage = RatingsImages::where('ratings_id', $ratingId)->first();

        if (!$ratingHasImage && !$thumbnail) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'error' => 'The selected rating id does not have a thumbnail image, please define a thumbnail.'
            ], 400);
        }

        $basePath = 'uploads/ratings/images';
        $urlBasePath = url('storage/' . $basePath);
        $file = $request->file('image');

        $image = new RatingsImages();
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
        $rating->images()->save($image);

        return new RatingImageResource($image);
    }

    public function update(Request $request, $ratingId, $id)
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

        $ratingImage = RatingsImages::findOrFail($id);

        if (isset($request['thumbnail']) && $request['thumbnail'] == true) {
            if ($ratingImage->thumbnail) {
                $image = $ratingImage;
            } else {
                $currentThumbnailImage = RatingsImages::where('ratings_id', $ratingId)->where('thumbnail', true)->first();

                if ($currentThumbnailImage) {
                    $currentThumbnailImage->update(['thumbnail' => false]);
                }

                $image = RatingsImages::where('id', $id)->update(['thumbnail' => true]);
            }
        }

        if (isset($request['title']) || isset($request['alt'])) {
            $title = $request['title'] ?? null;
            $alt = $request['alt'] ?? null;

            $image = RatingsImages::where('id', $id)->update(['title' => $title, 'alt' => $alt]);
        }

        if ($image) {
            return new RatingImageResource(RatingsImages::find($id));
        }

        return response()->json([
            'message' => 'could not update rating image data'
        ], 409);
    }

    public function destroy($ratingId, $id)
    {
        $rating = Ratings::findOrFail($ratingId);
        $image = RatingsImages::findOrFail($id);

        if ($image->ratings->id !== $rating->id) {
            return response()->json([
                'message' => "it's not possible to delete another rating's picture",
            ], 400);
        }

        $ratingImages = $rating->images;
        $imagesCount = sizeof($ratingImages);
        $isThumbnail = (bool) $image->thumbnail;

        if ($imagesCount > 1 && $isThumbnail) {
            return response()->json([
                'message' => 'it is not possible to delete a rating thumbnail',
            ], 400);
        }

        $deleteFile = Storage::delete('public/' . $image->path);
        $deleteEntity = $image->delete();

        if ($deleteEntity && $deleteFile) {
            return response()->json([], 204);
        }

        return response()->json([
            'message' => 'could not delete rating image data',
        ], 400);
    }
}
