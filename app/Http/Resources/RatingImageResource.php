<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RatingImageResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ratings_id' => $this->ratings_id,
            'title' => $this->title,
            'alt' => $this->alt,
            'thumbnail' => (bool) $this->thumbnail,
            'picture_url' => $this->picture_url,
            'filename' => $this->filename,
            'path' => $this->path,
            'mime' => $this->mime,
            'original_filename' => $this->original_filename,
            'original_extension' => $this->original_extension,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString()
        ];
    }
}
